<div class="panel">
    <div class="panel-heading">
        <h3 class="panel-title">热门未匹配</h3>
    </div>
    {if count($data.films) gt 0 }
        <ul class="list-group">
            {foreach from=$data.films item=film}
                <li class="list-group-item">
                    <div class="row">
                        <div class="col-sm-1 col-md-1">
                            <a href="/film/detail?id={$film.id}&debug=1" title="{$film.ch_name}">
                                <img style="max-width: 100%" src="{$PIC_HOST}{$film.l_post_cover}" alt="{$film.ch_name}海报"　/>
                            </a>
                        </div>
                        <div class="col-sm-1 col-md-1" style="width: 900px;">
                            <div class="row">
                                <p>{$film.ch_name} {$film.or_name}  ({$film.year})</p>
                            </div>
                            <div class="row">
                                <p>次数: {$film.ac_times}</p>
                            </div>
                            {if $film.other_names ne ""}
                                <div class="row">
                                    <p>又名:
                                        {$film.other_names}
                                    </p>
                                </div>
                            {/if}
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
        </ul>
    {/if}
</div>