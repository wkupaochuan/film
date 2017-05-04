<div class="panel">
	{if $data.genre_dic gt 0}
		<ul class="nav nav-pills" role="tablist">
			{foreach from=$data.genre_dic item=genre}
				{if $genre.genre_id eq $data.genre}
					<li role="presentation" class="active"><a href="/film/film_list?genre={$genre.genre_id}">{$genre.desc}</a></li>
				{else}
					<li role="presentation"><a href="/film/film_list?genre={$genre.genre_id}">{$genre.desc}</a></li>
				{/if}
			{/foreach}
		</ul>
	{/if}

	<ul class="list-group">
	{if count($data.search_res) gt 0 }
		{foreach from=$data.search_res item=film}
			<li class="list-group-item">
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
			</li>
		{/foreach}
	{/if}
		<li class="list-group-item">
			<div class="panel-heading">
				<a href="/film/film_list?genre={$data.genre}&page={$data.last}"><p>上一页</p></a>
				<a href="/film/film_list?genre={$data.genre}&page={$data.next}"><p>下一页</p></a>
			</div>
		</li>
	</ul>


</div>