<div class="panel">
	<div class="panel-heading">
		<h3 class="panel-title">搜索结果</h3>
	</div>
	{if count($data.search_res) gt 0 }
		{foreach from=$data.search_res item=film}
			<div class="row">
				<div class="col-xs-1 col-md-1">
					<a href="/film/detail?id={$film.id}">
						{if $film.b_post_cover ne ''}
							<img style="max-width: 100%" src="{$PIC_HOST}{$film.b_post_cover}" />
						{elseif $recom_film.l_post_cover ne ''}
							<img style="max-width: 100%" src="{$PIC_HOST}{$film.l_post_cover}" />
						{else}
							<img style="max-width: 100%" src="{$film.douban_post_cover}" />
						{/if}
					</a>
				</div>
				<div class="col-xs-1 col-md-1" style="width: 900px;">
					<div class="row">
						<p>{$film.ch_name} {$film.or_name}  ({$film.year})</p>
					</div>
					<div class="row">
						<p>导演:{$film.director}</p>
					</div>
					<div class="row">
						<p>演员: {$film.actors}</p>
					</div>
				</div>
			</div>
		{/foreach}
	{/if}

	<div class="panel-heading">
		<a href="/film/index?page={$data.last}"><p>上一页</p></a>
		<a href="/film/index?page={$data.next}"><p>下一页</p></a>
	</div>
</div>