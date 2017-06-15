<link rel="stylesheet" href="/resources/css/film_detail.css">

<header class="bar bar-nav">
	<a class="button button-link button-nav pull-left">
		<span class="icon icon-left"></span>
	</a>
	<a class="icon icon-refresh pull-right"></a>
	<h1 class="title">{$data.film_detail.ch_name}</h1>
</header>

<div class="content">
	<div class="card demo-card-header-pic">
		<div valign="bottom" class="card-header color-white no-border no-padding">
			{if $data.film_detail.b_post_cover ne ''}
				<img  class='card-cover' src="{$PIC_HOST}{$data.film_detail.b_post_cover}" />
			{elseif $data.film_detail.l_post_cover ne ''}
				<img class='card-cover' src="{$PIC_HOST}{$data.film_detail.l_post_cover}" />
			{else}
				<img class='card-cover' src="{$data.film_detail.douban_post_cover}" />
			{/if}
		</div>
	</div>

	<div class="card-content">
		<div class="list-block media-list">
			<ul>
				<li class="item-content">
					<div class="item-inner">
						<div class="item-title-row">
							<div class="item-title">标题</div>
						</div>
						<div class="item-subtitle">导演: {$data.film_detail.director} </div>
						<div class="item-subtitle">主演: {$data.film_detail.actors} </div>
						<div class="item-subtitle">类型: {$data.film_detail.genre} </div>
						<div class="item-subtitle">又名:
							{foreach from=$data.film_detail.other_names item=name}
								{$name}/
							{/foreach}
						</div>
					</div>
				</li>
			</ul>
		</div>
	</div>

	<div class="card">
		<div class="card-header">简介</div>
		<div class="card-content">
			<div class="card-content-inner">
				{$data.film_detail.summary}
			</div>
		</div>
	</div>

	<div class="swiper-container" data-space-between='10'>
		<div class="swiper-wrapper">
			{foreach from=$data.film_detail.related_pics item=pic}
				<div class="swiper-slide"><img src="{$PIC_HOST}{$pic.file_name}" alt=""></div>
			{/foreach}
		</div>
		<div class="swiper-pagination"></div>
	</div>

	{if count($data.film_detail.bt.thunder) gt 0}
		{foreach from=$data.film_detail.bt.thunder item=bt_batch}
		<div class="content-block-title">迅雷下载</div>
		<div class="list-block">
			<ul>
				{foreach from=$bt_batch item=bt}
					<li class="item-content">
						<div class="item-title">{$bt.name}</div>
						<div class="item-after"><p><a target="_self" href="{$bt.url}" thurl="{$bt.url}" mc="" title="迅雷下载" id="1thUrlid0" class="button button-light">迅雷下载</a></p></div>
					</li>
				{/foreach}
			</ul>
		</div>
		{/foreach}
	{/if}

	{if count($data.film_detail.bt.thunder) gt 0}
		{foreach from=$data.film_detail.bt.mag item=bt_batch}
			<div class="content-block-title">磁力下载</div>
			<div class="list-block">
				<ul>
					{foreach from=$bt_batch item=bt}
						<li class="content-block-title">

							<div class="item-inner">
								<div class="item-title">{$bt.name}</div>
							</div>
							<div class="item-media">
								<a target="_self" href="{$bt.url}" thurl="{$bt.url}" class="button button-light">迅雷下载</a>
							</div>
						</li>
					{/foreach}
				</ul>
			</div>
		{/foreach}
	{/if}

	{if count($data.film_detail.bt.thunder) gt 0}
		{foreach from=$data.film_detail.bt.bt item=bt_batch}
			<div class="content-block-title">bt下载</div>
			<div class="list-block">
				<ul>
					{foreach from=$bt_batch item=bt}
						<li class="item-content">
							<div class="item-title">{$bt.name}</div>
							<div class="item-after"><p><a target="_self" href="{$bt.url}" thurl="{$bt.url}" mc="" title="迅雷下载" id="1thUrlid0" class="button button-light">迅雷下载</a></p></div>
						</li>
					{/foreach}
				</ul>
			</div>
		{/foreach}
	{/if}

</div>