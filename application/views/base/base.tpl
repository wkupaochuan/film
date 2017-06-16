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
            电影饭_最新电影迅雷下载_bt种子下载
        {/if}
    </title>

    {if $data.keywords ne ""}
        <meta name="keywords" content="{$data.keywords}" />
    {else}
        <meta name="keywords" content="电影饭,最新电影,电影下载" />
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
                hm.src = "https://hm.baidu.com/hm.js?5a534333a93b667f2db709d67d37486a";
                var s = document.getElementsByTagName("script")[0];
                s.parentNode.insertBefore(hm, s);
            })();
        </script>

        <script>
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

            ga('create', 'UA-100324604-1', 'auto');
            ga('send', 'pageview');

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
