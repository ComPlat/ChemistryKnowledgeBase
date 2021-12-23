<?php
use DIQA\WikiFarm\Special\SpecialCreateWiki;
?>
<div id="wfarm-createdwikis-table" class="wfarm-createdwikis-table">
<p>{{wfMessage('wfarm-wikis-created-by-you')}}</p>
<table>
        <tr>
            <th>{{wfMessage('wfarm-wiki-url')}}</th>
            <th>{{wfMessage('wfarm-wiki-name')}}</th>
            <th>{{wfMessage('wfarm-wiki-creation-date')}}</th>
        </tr>
    @foreach($allWikiCreated as $row)
        <tr wiki-id="{{$row['id']}}" class="{{$row['wiki_status'] === 'IN_CREATION' ? "wfarm-in-creation": ""}}">
            <td>
                @if($row['wiki_status'] === 'CREATED')
                <a target="_blank" href="{{$baseURL}}/wiki{{$row['id']}}/mediawiki">Open</a>
                @endif
            </td>
            <td>{{$row['wiki_name']}}</td>
            <td>{{$row['created_at']}} {{SpecialCreateWiki::within2Days($row['created_at']) ? "(".wfMessage("wfarm-recent").")": ""}}</td>
        </tr>
    @endforeach
</table>
@if (count($allWikiCreated) === 0)
<p>{{wfMessage('wfarm-no-wikis-found')}}</p>
@endif
</div>