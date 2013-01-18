---
layout: post
title: "PHP 验证码"
date: 2013-01-13 14:20
comments: true
categories: [php]
---

gd是一个强大的php图像处理库，最近在做验证码加强的策略，才发现用php作图也能玩出很多花样来。

## 几个重要函数
* [imagecreatetruecolor](http://php.net/manual/en/function.imagecreatetruecolor.php) 创建一张空的画布
* [imagecreatefrompng](http://cn2.php.net/manual/zh/function.imagecreatefrompng.php) 从文件创建一个图片句柄
* [imagecolorallocate](http://cn2.php.net/manual/zh/function.imagecolorallocate.php) 拾取一种颜色(rgb)
* [imagettftext](http://cn2.php.net/manual/zh/function.imagettftext.php) 向画布写入文字
* [imagecopy](http://cn2.php.net/manual/zh/function.imagecopy.php) 合并两张图片，可指定拷贝区域及大小
* [imagecolorat](http://cn2.php.net/manual/zh/function.imagecolorat.php) 从图片指定像素点拾取一种颜色
* [imagesetpixel](http://cn2.php.net/manual/zh/function.imagesetpixel.php) 画一个像素点
* [imagearc](http://cn2.php.net/manual/zh/function.imagearc.php) 画一个椭圆，截取部分可用来绘制曲线


php绘图用的最频繁的地方大概就是生成验证码了，我们最常见的验证码数字加英文的组合，生成这种验证码很简单，下面几行代码就可以搞定

{% codeblock lang:php %}
<?php
public function genCode($n = 4) {
    $dict = 'ABCDEFGHIJKLNMPQRSTUVWXYZ123456789';
    $dictlen = strlen($dict);
    $image = $this->image;
    $verify = '';
    $fontfile = $this->sourcedir . $this->fonts[0];
    $colors = array(
        imagecolorallocate($image, 255, 0, 0) , //红
        imagecolorallocate($image, 0, 0, 255) , //蓝
        imagecolorallocate($image, 0, 0, 0) , //黑
    );
    for ($i = 0;$i < $n;$i++) {
        $verify.= $code = substr($dict, mt_rand(0, $dictlen - 1) , 1);
        imagettftext($image, 20, mt_rand(-15, 15) , ($i * 15) + 3, mt_rand(20, 25) , $colors[array_rand($colors) ], $fontfile, $code);
    }
    return $this;
}
{% endcodeblock %}

效果图：

> {% img /images/u/verify-code-simple.png "verify-code-simple" %}

其中合并了一张纹理背景并随机绘制出文字的颜色。下面我们再加点料，

{% codeblock lang:php %}
<?php
public function addNoise($n = 50) {
    $image = $this->image;
    $color = imagecolorallocate($image, 0, 0, 0);
    for ($i = 0;$i < $n;$i++) { //噪声点
        imagesetpixel($image, mt_rand(0, $this->width) , mt_rand(0, $this->height) , $color);
    }
    return $this;
}
public function addLine($n = 1) {
    $image = $this->image;
    $color = imagecolorallocate($image, 0, 0, 0);
    for ($i = 0;$i < $n;$i++) {
        imagearc($image, rand(-10, $this->width + 10) , rand(-10, 0) , rand($this->width * 2 + 10, $this->width * 2 + 40) , rand($this->height, $this->height + 20) , 0, 360, $color);
    }
    return $this;
}
{% endcodeblock %}

上面的方法往图像中加入了50个噪点和一条干扰曲线，于是验证码变成了这样：

> {% img /images/u/verify-code-noise.png "verify-code-noise" %}

下面来实现汉字和带公式的验证码
{% codeblock lang:php %}
<?php
public function genHanzi($n = 2) {
    $dict = "的一是在了不和有大这主中人上为们地个用工时要";
    $dictlen = mb_strlen($dict, 'UTF-8');
    $image = $this->image;
    $fontfile = $this->sourcedir . $this->fonts[array_rand($this->fonts) ];
    $color = imagecolorallocate($image, 0, 0, 0);
    $verify = '';
    for ($i = 0;$i < $n;$i++) {
        $verify.= $word = mb_substr($dict, mt_rand(0, $dictlen - 1) , 1, 'UTF-8');
        imagettftext($image, rand(18, 22) , rand(-20, 20) , 5 + $i * 25, 25, $color, $fontfile, $word);
    }
    $this->verify = $verify;
    return $this;
}

public function genFomula() {
    $symbols = array(
        '＋' => '+','－' => '-','×' => '*','加' => '+','减' => '-','乘' => '*'
    );
    $numbers = array(
        '0' => 0,'1' => 1,'2' => 2,'3' => 3,'4' => 4,'5' => 5，'叁' => 3,'肆' => 4,'伍' => 5,'陆' => 6,'柒' => 7,'捌' => 8,'玖' => 9,
    );
    $image = $this->image;
    $fontfile = $this->sourcedir . $this->fonts[array_rand($this->fonts) ];
    $numidx1 = array_rand($numbers);
    $num1 = $numbers[$numidx1];
    $symbol = array_rand($symbols);
    $color = imagecolorallocate($image, 0, 0, 0);
    while (1) {
        $numidx2 = array_rand($numbers);
        $num2 = $numbers[$numidx2];
        if ($symbols[$symbol] != '-' || $num2 <= $num1) { //减法结果不为负数
            break;
        }
    }
    eval("\$verify = " . "$num1" . $symbols[$symbol] . "$num2;");
    $verify = intval($verify);
    $codelist = array(
        $numidx1,
        $symbol,
        $numidx2,
        '='
    );
    foreach ($codelist as $i => $code) {
        imagettftext($image, mt_rand(14, 16) , mt_rand(-15, 15) , ($i * 18) + 3, mt_rand(20, 25) , $color, $fontfile, $code);
    }
    return $this;
}
{% endcodeblock %}

生成汉字和上面的英文组合差不多，加个字典就可以，然后可以加入一下随机的字体变换，生成公式呢，其实也是预先定义好数字和符号的字典，靠随机组合来生成图片，然后吧计算结果记录下来就行了。

> {% img /images/u/verify-hanzi.png "verify-hanzi" %}
> {% img /images/u/verify-fomula.png "verify-fomula" %}

很多验证码中还会对字体进行扭曲，这会让做的人和看的人都比较纠结。目前的方法大致是先生成一张正常的图，然后拾取图中每个像素点进行正弦变换位置后填入另一张相同大小的图，注意两张图的背景需要一致，否则边缘的图片就很不和谐咯。

{% codeblock lang:php %}
public function twist() {
    $distImage = imagecreatetruecolor($this->width, $this->height);
    imagecopy($distImage, $this->backimg, 0, 0, 0, 0, $this->width, $this->height);
    for ($x = 0;$x < $this->width;$x++) {
        for ($y = 0;$y < $this->height;$y++) {
            $rgb = imagecolorat($this->image, $x, $y);
            imagesetpixel($distImage, (int)($x + sin($y / $this->height * 2 * M_PI - M_PI * 0.1) * 4) , $y, $rgb);
        }
    }
    $this->image = $distImage;
    return $this;
}
{% endcodeblock %}

效果图：

> {% img /images/u/verify-code-twist.png "verify-code-twist" %}

最后再加入一个gif动态图的例子，主要原理是预先生成每一帧的gif图像，然后合并为一张图片，对gif进行编码的类库使用的是网上下载的GIFEncoder，代码不多，但是够用。`less is more`嘛。

{% codeblock lang:php %}
public function genCodeAnimate($n = 4, $flags = 40) {
    $dict = 'ABCDEFGHIJKLNMPQRSTUVWXYZ123456789';
    $dictlen = strlen($dict);
    $verify = '';
    $fontfile = $this->sourcedir . $this->fonts[0];
    $colors = array(
        imagecolorallocate($this->image, 255, 0, 0) , //红
        imagecolorallocate($this->image, 0, 0, 255) , //蓝
        imagecolorallocate($this->image, 0, 0, 0) , //黑
    );
    $fontColors = array();
    $fontSizes = array();
    $gifs = array();
    for ($i = 0;$i < $n;$i++) {
        $verify.= substr($dict, mt_rand(0, $dictlen - 1) , 1);
        $fontColors[$i] = $colors[array_rand($colors) ];
        $fontSizes[$i] = rand(18, 22);
    }
    for ($f = 0;$f < $flags;$f++) {
        $image = $this->imgClone($this->image);
        $angle = - 15 + abs($f - $flags / 2) * 2; //角度
        $y = 20 + abs($f - $flags / 2) * 0.5;
        for ($i = 0;$i < $n;$i++) {
            $code = substr($verify, $i, 1);
            imagettftext($image, $fontSizes[$i], $angle, ($i * 15) - 20 + abs($f - $flags / 2) * 5, $y, $fontColors[$i], $fontfile, $code);
        }
        header("Content-type: image/gif");
        imagegif($image);
        imagedestroy($image);
        $gifs[] = ob_get_contents();
        ob_clean();
    }
    ob_start();
    $gifEncoder = new GIFEncoder($gifs, 100, 0, 1, 0, 0, 1, 'bin');
    header('Content-type: image/gif');
    echo $gifEncoder->GetAnimation();
    return $verify;
}
{% endcodeblock %}

效果图：

> {% img /images/u/verify-code-animate.gif "verify-code-animate" %}

## 备忘
* `imagecreate`也是一个创建图像的方法，不过相对于`imagecreatetruecolor`，它会使用第一次由`imagecolorallocate`生成的颜色作为背景色，比较坑爹，不推荐。

## 下载
* [源码](/patches/verify.zip)
