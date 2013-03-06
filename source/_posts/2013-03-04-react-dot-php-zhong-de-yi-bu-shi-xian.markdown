---
layout: post
title: "react.php 中的异步实现"
date: 2013-03-04 15:29
comments: true
categories: [php, react]
---

在基于事件的异步模型大行其道的今天，各语言都相继推出了自己的异步框架，nodejs原生的异步模型当然是其中的翘楚，然后python有Twisted，ruby有EventMachine(名字俗了点)。php也有一个不错的异步框架，[react](https://github.com/reactphp/react)。下面我们从内而外的看看这个异步模型是怎么实现的。

###événement

首先react的事件模型是建立在一个叫[événement](https://github.com/igorw/evenement)的框架上，也是react作者所作，代码只有短短的74行，实现了`on`(事件监听),`emit`(触发事件)等方法。下面就单就这两个事件方法分析一下。

{% codeblock lang:php %}
<?php
public function on($event, $listener)
    {
        if (!is_callable($listener)) {
            throw new \InvalidArgumentException('The provided listener was not a valid callable.');
        }

        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = array();
        }

        $this->listeners[$event][] = $listener;
    }
public function emit($event, array $arguments = array())
    {
        foreach ($this->listeners($event) as $listener) {
            call_user_func_array($listener, $arguments);
        }
    }
?>
{% endcodeblock %}

上面的`$event`其实就是一个事件标识，一般是一个字符串，`$listener`是一个回调方法。调用`on`时用数组listeners记录所有回调方法，调用`emit`时再按次序触发，由此还衍生了`once`(只触发一次就解除绑定的事件)，`removeListener`(移除事件)等方法。

###EventLoop

更进一步，react事件模型的上一层是一个监听循环，叫做`EventLoop`，有了这个，就往消息队列或webserver的异步处理模型更近了一步。

下面可以看一下`EventLoop`的接口文件`LoopInterface.php`：

{% codeblock lang:php %}
<?php
namespace React\EventLoop;
interface LoopInterface
{
    public function addReadStream($stream, $listener);
    public function addWriteStream($stream, $listener);

    public function removeReadStream($stream);
    public function removeWriteStream($stream);
    public function removeStream($stream);

    public function addTimer($interval, $callback);
    public function addPeriodicTimer($interval, $callback);
    public function cancelTimer($signature);

    public function tick();
    public function run();
    public function stop();
}
?>
{% endcodeblock %}

`react`支持php的社区库`libevent`提供的事件支持，同时有个`LibEventLoop.php`用来实现`LoopInterface`接口，但是`react`也有自己的实现方案`StreamSelectLoop`，与`LibEventLoop`不兼容，这点可以在`EventLoop\LibEventLoop\Factory`中看到。

{% codeblock lang:php %}
<?php
    public static function create()
    {
        // @codeCoverageIgnoreStart
        if (function_exists('event_base_new')) { //使用libevent
            return new LibEventLoop();
        }
        return new StreamSelectLoop(); //不使用libevent
        // @codeCoverageIgnoreEnd
    }
?>
{% endcodeblock %}

我们主要来看看`StreamSelectLoop`的实现。`LoopInterface`中几个重要的方法`addReadStream`, `addWriteStream`, `addTimer`, `tick`都可以在`StreamSelectLoop`找到踪影。下面先说一下`addReadStream`中的两个参数：

*`$stream`是一个由`stream_socket_server`方法生成的socket句柄，支持tcp或文件socket等方式。
*`$listener`其实就是一个callback方法，在这个方法中就需要实现具体的应用逻辑了。

`addWriteStream`与`addReadStream`方法差不多，这两个方法其实啥都没做，只是注册一下两个方法，以供后面调用。真正起作用的方法是`tick`和`runStreamSelect`。

{% codeblock lang:php %}
<?php
    protected function runStreamSelect()
    {
        $read = $this->readStreams ?: null;
        $write = $this->writeStreams ?: null;
        $except = null;

        if (!$read && !$write) {
            $this->sleepOnPendingTimers();

            return;
        }

        if (stream_select($read, $write, $except, 0, $this->getNextEventTimeInMicroSeconds()) > 0) {
            if ($read) {
                foreach ($read as $stream) {
                    $listener = $this->readListeners[(int) $stream];
                    if (call_user_func($listener, $stream, $this) === false) {
                        $this->removeReadStream($stream);
                    }
                }
            }

            if ($write) {
                foreach ($write as $stream) {
                    if (!isset($this->writeListeners[(int) $stream])) {
                        continue;
                    }

                    $listener = $this->writeListeners[(int) $stream];
                    if (call_user_func($listener, $stream, $this) === false) {
                        $this->removeWriteStream($stream);
                    }
                }
            }
        }
    }
?>
{% endcodeblock %}

`runStreamSelect`方法在`tick`方法中被调用，目的是在每个间隔中重复调用之前绑定的`$listener`方法，这个可以理解，因为本来`EventLoop`的目的就是实现事件的监听，监听的最简单方法就是通过轮询的方式来调用，假如某些方法不希望被重复调用或者希望在某次成功之后就不再调用，那么在定义`$listener`方法时，将返回值设置成false即可。至于这个间隔，则是通过`Timer`来实现的。

在`runStreamSelect`中调用了一个有意思的方法[`stream_select`](http://php.net/manual/en/function.stream-select.php)，用timeout取代传统的`sleep`，并兼具监听socket端口的功能，一旦有新的连接或者改动，`stream_select`会立刻返回read或write中被修改的socket连接总数。这样，既能合理的释放cpu资源，又能及时对事件发起响应。比起传统的`while+sleep`，实在是高明很多。

###Timer

说到`tick`就不得不提`Timer`，`react`中用一个`Timer`类来模拟步进操作。每隔一定的ticket会唤起一个事件，这样才能保证异步操作能正确的被调用，这个ticket的数量有讲究，设置的太小会让系统耗用大量资源，设置的太大又不能保证异步事件能及时的被调用。下面我们来看看`Timer`中的tick。

`Timer`中使用一个队列来记录所有将触发的事件，并且将它们按照优先级(也就是触发事件)排序，最后每次调用优先级最高的事件。

`react`中使用`SplPriorityQueue`类来做优先级队列，这是php5.3后新增的一个标准库类，其功能很简单，就是实现一个按照rank排序的队列，有点类似redis中的zset，但是其值是可以重复的，所以它不是一个集合。它实现了`insert`, `count`, `count`, `extract`等方法，通过`insert`往队列中插入的数据会自动按照优先级(priority)由小到大排序，免去了sort的麻烦，然后可以通过`extract`方法得到队列顶部优先级(priority)最小的数据。

有了Timer，用户就可以给`react`设置延迟事件，可以参考js中的`setTimeout`方法。

最后，`react`通过`EventLoop`中的`run`方法将tick放入一个while循环，实现了监听的目的。

总结一下，`react`的异步事件模型：

核心的on-emit事件模型 --> EventLoop实现响应socket事件 --> Timer实现事件优先级排序 --> while循环来pending服务端的程序。

`react`还提供了很多的示例文件，一个简单的http-server可以实现如下：

{% codeblock lang:php %}
<?php
require __DIR__.'/../vendor/autoload.php';
$i = 0;
$app = function ($request, $response) use (&$i) {
    $i++;
    $text = "This is request number $i.\n";
    $headers = array('Content-Type' => 'text/plain');
    $response->writeHead(200, $headers);
    $response->end($text);
};
$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($loop);
$http = new React\Http\Server($socket);
$http->on('request', $app);
$socket->listen(1337);
$loop->run();
?>
{% endcodeblock %}

##后记

`react`中用到了很多php5.3之后才出现的新特性(EventEmitter2甚至只支持5.4以上的php版本)，很多方法以前也没有接触过，可以说相比于国内对php的应用，老外对语言的研究更加透彻。想起国内这么普及的php应用，很多人只是略知皮毛，就喜欢大言不惭，甚至能人云亦云的挑出一堆语言的弊端。老外的钻研精神确实值得学习。

记录一下几个可能有用但是不常用的类或方法：

* [`stream_socket_server`](http://php.net/manual/en/function.stream-socket-server.php) 创建一个服务端套接字。
* [`stream_select`](http://php.net/manual/en/function.stream-select.php) 监听读写socket状态的变化，带timeout时间
* [`SplPriorityQueue`](http://www.php.net/manual/en/splpriorityqueue.insert.php) 创建一个带优先级的有序队列

##参考文档

[Taming SplPriorityQueue](http://www.mwop.net/blog/253-Taming-SplPriorityQueue.html)