<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="initial-scale=1, maximum-scale=1">
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

    <link rel="stylesheet" href="//g.alicdn.com/msui/sm/0.6.2/css/sm.min.css">
    <link rel="stylesheet" href="//g.alicdn.com/msui/sm/0.6.2/css/sm-extend.min.css">

    <!--百度统计-->
    {if $ENV eq 'production'}
    {literal}


    {/literal}
    {/if}
</head>

<body>

<div class="page-group">
    <div class="page">
        {include file="base/header.tpl"}
        <div class="content">
            {include file=$content_html}
        </div>
    </div>
    {include file="base/footer.tpl"}
</div>





<script type='text/javascript' src='//g.alicdn.com/sj/lib/zepto/zepto.min.js' charset='utf-8'></script>
<script type='text/javascript' src='//g.alicdn.com/msui/sm/0.6.2/js/sm.min.js' charset='utf-8'></script>
<script type='text/javascript' src='//g.alicdn.com/msui/sm/0.6.2/js/sm-extend.min.js' charset='utf-8'></script>
<script>
    $.init();
</script>
</body>

</html>
