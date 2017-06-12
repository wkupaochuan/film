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
		<h3 class="panel-title">最近更新</h3>
	</div>

	{if count($data.search_res) gt 0 }
			<div class="container">
			{foreach from=$data.search_res key=index item=film}
				{if $index%6 eq 0 }
					<div class="row">
				{/if}
						<div class="col-md-2">
							<div class="thumbnail">
								<a href="/film/detail?id={$film.id}" title="{$film.ch_name}">
									{if $film.b_post_cover ne ''}
										<img class="img-responsive"  src="{$PIC_HOST}{$film.b_post_cover}" alt="{$film.ch_name}海报" />
									{elseif $recom_film.l_post_cover ne ''}
										<img class="img-responsive"  src="{$PIC_HOST}{$film.l_post_cover}" alt="{$film.ch_name}海报"　/>
									{else}
										<img class="img-responsive" src="{$film.douban_post_cover}" alt="{$film.ch_name}海报"/>
									{/if}
								</a>
								<div class="caption">
									<p><a href="/film/detail?id={$film.id}')" class="" >{$film.ch_name}</a></p>
								</div>
								<p></p>
							</div>
						</div>
				{if ($index+1)%6 eq 0 }
					</div>
				{/if}
			{/foreach}
			</div>
	{/if}
</div>