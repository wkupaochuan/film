<div style="padding: 10px 10px 10px;">
	<form class="bs-example bs-example-form" role="form" name="dd" action="/film/index?" method="get">
		<div class="input-group input-group-lg">
			<input type="text" name="film_name" class="form-control" placeholder="肖申克的救赎" value="{$data.search_words}">
			<span class="input-group-addon" onclick="javascript:dd.submit();" ><a>搜索</a></span>
		</div>
	</form>
</div>

<div class="panel">
	<div class="panel-heading">
		<h3 class="panel-title">搜索结果</h3>
	</div>
	{if count($data.search_res) gt 0 }
		<ul class="list-group">
			{foreach from=$data.search_res item=film}
				<li class="list-group-item">
					<div class="row">
						<div class="col-sm-1 col-md-1">
							<a href="/film/detail?id={$film.id}" title="{$film.ch_name}">
								{if $film.b_post_cover ne ''}
									<img style="max-width: 100%" src="{$PIC_HOST}{$film.b_post_cover}" alt="{$film.ch_name}海报" />
								{elseif $recom_film.l_post_cover ne ''}
									<img style="max-width: 100%" src="{$PIC_HOST}{$film.l_post_cover}" alt="{$film.ch_name}海报"　/>
								{else}
									<img style="max-width: 100%" src="{$film.douban_post_cover}" alt="{$film.ch_name}海报"/>
								{/if}
							</a>
						</div>
						<div class="col-sm-1 col-md-1" style="width: 900px;">
							<div class="row">
								<p>{$film.ch_name} {$film.or_name}  ({$film.year})</p>
							</div>
							{if count($film.other_names) gt 0}
								<div class="row">
									<p>又名:
										{foreach from=$film.other_names item=name}
											{$name}/
										{/foreach}
									</p>
								</div>
							{/if}
							<div class="row">
								<p>导演:{$film.director}</p>
							</div>
							<div class="row">
								<p>演员: {$film.actors}</p>
							</div>
							<div class="row">
								<p>简介: {$film.summary}</p>
							</div>
						</div>
					</div>
				</li>
			{/foreach}
		</ul>
	{/if}
</div>