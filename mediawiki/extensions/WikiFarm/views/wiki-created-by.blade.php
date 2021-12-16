<?php
use DIQA\WikiFarm\Special\SpecialCreateWiki;
?>
<div id="wfarm-createdwikis-table" class="wfarm-createdwikis-table">
<p>{{wfMessage('wfarm-wikis-created-by-you')}}</p>
<table style="width: 80%">
        <tr>
            <th>URL</th>
            <th>Name</th>
            <th>Creation date</th>
        </tr>
    @foreach($allWikiCreated as $row)
        <tr class="{{$row['wiki_status'] === 'IN_CREATION' ? "wfarm-in-creation": ""}} {{SpecialCreateWiki::within2Days($row['created_at']) ? "wfarm-recent": ""}}">
            <td>
                @if($row['wiki_status'] === 'CREATED')
                <a target="_blank" href="{{$baseURL}}/wiki{{$row['id']}}/mediawiki">Open</a>
                @endif
            </td>
            <td>{{$row['wiki_name']}}</td>
            <td>{{$row['created_at']}}</td>
        </tr>
    @endforeach
</table>
@if (count($allWikiCreated) === 0)
<p>{{wfMessage('wfarm-no-wikis-found')}}</p>
@endif
</div>