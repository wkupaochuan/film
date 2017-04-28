<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- 适配移动设备 -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"">
    <!-- 上述3个meta标签*必须*放在最前面，任何其他内容都*必须*跟随其后！ -->
    <title>
        {if $data.title ne ""}
            {$data.title}
        {else}
            电影大观_最新电影迅雷下载_bt种子下载
        {/if}
    </title>

    {if $data.keywords ne ""}
        <meta name="keywords" content="{$data.keywords}" />
    {else}
        <meta name="keywords" content="电影大观,最新电影,电影下载" />
    {/if}

    {if $data.description ne ""}
        <meta name="description" content="{$data.description}" />
    {else}
        <meta name="description" content="" />
    {/if}


    <!-- Bootstrap -->
    <link href="../../../bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!--百度统计-->
    {if $ENV eq 'production'}
    {literal}
        <script language="JavaScript" type="text/javascript">
            var _hmt = _hmt || [];
            (function() {
                var hm = document.createElement("script");
                hm.src = "https://hm.baidu.com/hm.js?bb663b6933fc393d2ae8876018a3ba0d";
                var s = document.getElementsByTagName("script")[0];
                s.parentNode.insertBefore(hm, s);
            })();

            (function(){
                var bp = document.createElement('script');
                var curProtocol = window.location.protocol.split(':')[0];
                if (curProtocol === 'https') {
                    bp.src = 'https://zz.bdstatic.com/linksubmit/push.js';
                }
                else {
                    bp.src = 'http://push.zhanzhang.baidu.com/push.js';
                }
                var s = document.getElementsByTagName("script")[0];
                s.parentNode.insertBefore(bp, s);
            })();
        </script>
    {/literal}
    {/if}
</head>

<body>
{include file="base/header.tpl"}
<div class="container">
    {include file=$content_html}
</div>
{include file="base/footer.tpl"}

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="https://cdn.bootcss.com/jquery/1.12.4/jquery.min.js"></script>
<!-- Include all compiled plugins (below), or include individual files as needed -->
<script src="../../../bootstrap/js/bootstrap.min.js"></script>
</body>

</html>
