<header class="bar bar-nav">
	<form class="bs-example bs-example-form" role="form" name="dd" action="/film/index?" method="get">
		<div class="bar bar-header-secondary">
			<div class="searchbar">
				<a class="searchbar-cancel">取消</a>
				<div class="search-input">
					<label class="icon icon-search" for="search"></label>
					<input type="search" name="film_name" class="form-control" placeholder="肖申克的救赎" value="{$data.search_words}">
					{*<span class="input-group-addon" onclick="javascript:dd.submit();" ><a>搜索</a></span>*}
				</div>
			</div>
		</div>
	</form>
</header>

<div class="content">
	{if count($data.search_res) gt 0 }
		<div class="list-block media-list">
			<ul>
				{foreach from=$data.search_res item=film}
					<li>
						<a href="/film/detail?id={$film.id}" title="{$film.ch_name}" class="item-link item-content">
							<div class="item-media">
								{if $film.l_post_cover ne ''}
									<img style='width: 4rem;' src="{$PIC_HOST}{$film.l_post_cover}" alt="{$film.ch_name}海报"　/>
								{elseif $film.b_post_cover ne ''}
									<img style='width: 4rem;' src="{$PIC_HOST}{$film.b_post_cover}" alt="{$film.ch_name}海报" />
								{else}
									<img style='width: 4rem;' src="{$film.douban_post_cover}" alt="{$film.ch_name}海报"/>
								{/if}
							</div>
							<div class="item-inner">
								<div class="item-title-row">
									<div class="item-title">{$film.ch_name} {$film.or_name}  ({$film.year})</div>
								</div>
								<div class="item-subtitle">导演:{$film.director}</div>
								<div class="item-subtitle">演员: {$film.actors}</div>
								<div class="item-text">简介: {$film.summary}</div>
							</div>
						</a>
					</li>
				{/foreach}
			</ul>
		</div>
	{/if}
</div>