<link rel="stylesheet" href="/resources/css/film_detail.css">

<header class="bar bar-nav">
	<a class="button button-link button-nav pull-left">
		<span class="icon icon-left"></span>
	</a>
	<a class="icon icon-refresh pull-right"></a>
	<h1 class="title">{$data.film_detail.ch_name}</h1>
</header>

<div class="content">
	<h1>
		<span >{$data.film_detail.ch_name} {$data.film_detail.or_name} ({$data.film_detail.year}) </span>
	</h1>
	<div class="grid-16-8 clearfix">
		<!--简介-->
		<div class="indent subjectwrap subject clearfix">
			<div id="mainpic" class="">
				{if $data.film_detail.b_post_cover ne ''}
					<img style="max-width: 100%" src="{$PIC_HOST}{$data.film_detail.b_post_cover}" />
				{elseif $data.film_detail.l_post_cover ne ''}
					<img style="max-width: 100%" src="{$PIC_HOST}{$data.film_detail.l_post_cover}" />
				{else}
					<img style="max-width: 100%" src="{$data.film_detail.douban_post_cover}" />
				{/if}
			</div>
			<div id="info" class="">
				<span ><span class='pl'>导演</span>: <span class='attrs'>{$data.film_detail.director}</span></span><br/>
				<span class="actor"><span class='pl'>主演</span>: {$data.film_detail.actors} </span><br/>
				<span class="pl">类型: {$data.film_detail.genre}</span><br/>
				<span class="pl">片长:</span> <span property="v:runtime" content="117">{$data.film_detail.runtime}</span><br/>
				{if $data.film_detail.douban_rate ne ""}
					<span class="pl">豆瓣评分:</span> <span property="v:runtime" content="117">{$data.film_detail.douban_rate}</span><br/>
				{/if}

				{if count($data.film_detail.other_names) gt 0 }
					<span class="pl">又名:</span>
					{foreach from=$data.film_detail.other_names item=name}
						{$name}/
					{/foreach}
					<br/>
				{/if}
			</div>
		</div>

		<!--剧情简介-->
		<div class="related-info" style="margin-bottom:-10px;">
			<h4>{$data.film_detail.ch_name}的剧情简介</h4>
			<div class="indent" id="link-report">
				<span class="">
					{$data.film_detail.summary}
				</span>
			</div>
		</div>

		<!--海报-->
		{if count($data.film_detail.related_pics) gt 0}
			<div  class="related-pic">
				<h4><i class="">{$data.film_detail.ch_name}的图片</i>· · · · · ·</h4>
				<ul class="related-pic-bd">
					{foreach from=$data.film_detail.related_pics item=pic}
						<li>
							<img class="film_rel_pic" src="{$PIC_HOST}{$pic.file_name}" />
						</li>
					{/foreach}
				</ul>
			</div>
		{/if}

		<!--下载资源-->
		{if count($data.film_detail.bt.thunder) gt 0}
			{foreach from=$data.film_detail.bt.thunder item=bt_batch}
				<div  class="related-pic">
					<h4>迅雷下载</h4>
					<ul class="downurl">
						{foreach from=$bt_batch item=bt}
							<li>
								<div class="loldytt">
									<div>
										<span>{$bt.name}</span>
										<input type="text" value="{$bt.url}" />
									</div>
								</div>
								<div class="dwon_xl"><a target="_self" href="{$bt.url}" thurl="{$bt.url}" mc="" title="迅雷下载" id="1thUrlid0" class="dwon1">迅雷下载</a></div>
							</li>
						{/foreach}
					</ul>
				</div>
			{/foreach}
		{/if}

		{if count($data.film_detail.bt.bt) gt 0}
			{foreach from=$data.film_detail.bt.bt item=bt_batch}
				<div  class="related-pic">
					<h4>磁力下载</h4>
					<ul class="downurl">
						{foreach from=$bt_batch item=bt}
							<li>
								<div class="loldytt">
									<div>
										<span>{$bt.name}</span>
										<input type="text" value="{$bt.url}" />
									</div>
								</div>
								<div class="dwon_xl"><a target="_self" href="{$bt.url}" thurl="{$bt.url}" mc="" title="迅雷下载" id="1thUrlid0" class="dwon1">迅雷下载</a></div>
							</li>
						{/foreach}
					</ul>
				</div>
			{/foreach}
		{/if}

		{if count($data.film_detail.bt.mag) gt 0}
			{foreach from=$data.film_detail.bt.mag item=bt_batch}
				<div  class="related-pic">
					<h4>bt下载</h4>
					<ul class="downurl">
						{foreach from=$bt_batch item=bt}
							<li>
								<div class="loldytt">
									<div>
										<span>{$bt.name}</span>
										<input type="text" value="{$bt.url}" />
									</div>
								</div>
								<div class="dwon_xl"><a target="_self" href="{$bt.url}" thurl="{$bt.url}" mc="" title="迅雷下载" id="1thUrlid0" class="dwon1">迅雷下载</a></div>
							</li>
						{/foreach}
					</ul>
				</div>
			{/foreach}
		{/if}


		<!--获奖-->
		<!--
		<div class="mod">
			<div class="hd">
				<h4><i class="">当幸福来敲门的获奖情况</i> · · · · · · </h4>
			</div>

			<ul class="award">
				<li>
					<a href="https://movie.douban.com/awards/Oscar/79/">第79届奥斯卡金像奖</a>
				</li>
				<li>最佳男主角(提名)</li>
				<li><a href='https://movie.douban.com/celebrity/1027138/' target='_blank'>威尔·史密斯</a></li>
			</ul>
			<ul class="award">
				<li>
					<a href="https://movie.douban.com/awards/mtvma/16/">第16届MTV电影奖</a>
				</li>
				<li>MTV电影奖 最佳表演(提名)</li>
				<li><a href='https://movie.douban.com/celebrity/1027138/' target='_blank'>威尔·史密斯</a></li>
			</ul>

			<ul class="award">
				<li>
					<a href="https://movie.douban.com/awards/mtvma/16/">第16届MTV电影奖</a>
				</li>
				<li>MTV电影奖 突破表演奖</li>
				<li><a href='https://movie.douban.com/celebrity/1010532/' target='_blank'>贾登·史密斯</a></li>
			</ul>
		</div>
		-->

		<!--类似推荐-->
		{if count($data.film_detail.recom_films) gt 0}
			<div id="recommendations">
				<h4> <i class="">喜欢这部电影的人也喜欢</i> · · · · · · </h4>
				<div class="recommendations-bd">
					{foreach from=$data.film_detail.recom_films item=recom_film}
						<dl >
							<dt>
								<a href="/film/detail?id={$recom_film.id}')" title="{$recom_film.ch_name}">
									{if $recom_film.b_post_cover ne ''}
										<img style="max-width: 100%" src="{$PIC_HOST}{$recom_film.b_post_cover}" />
									{elseif $recom_film.l_post_cover ne ''}
										<img style="max-width: 100%" src="{$PIC_HOST}{$recom_film.l_post_cover}" />
									{else}
										<img style="max-width: 100%" src="{$recom_film.douban_post_cover}" />
									{/if}
								</a>
							</dt>
							<dd>
								<a href="/film/detail?id={$recom_film.id}')" class="" >{$recom_film.ch_name}</a>
							</dd>
						</dl>
					{/foreach}
				</div>
			</div>
		{/if}

		{if count($data.film_detail.comments) gt 0}
			<!--评价-->
			<div >
				<div >
					<h4>{$data.film_detail.ch_name}的评价</h4>
				</div>
				{foreach from=$data.film_detail.comments item=comment}
					<div >
						<h4 class="review_user">{$comment.user}</h4>
						<p class="review_content">{$comment.content}</p>
					</div>
				{/foreach}
			</div>
		{/if}

	</div>
</div>