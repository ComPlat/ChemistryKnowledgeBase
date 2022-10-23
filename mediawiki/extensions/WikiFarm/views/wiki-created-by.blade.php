<?php
use DIQA\WikiFarm\Special\SpecialCreateWiki;
use OOUI\ButtonWidget;
$specialPage = $specialPage ?? null; // to prevent IDE warning
?>
<div id="wfarm-createdwikis-table" class="wfarm-createdwikis-table">
<p>{{wfMessage('wfarm-wikis-created-by-you')}}</p>
<table>
        <tr>
            <th>{{wfMessage('wfarm-wiki-name')}}</th>
            <th>{{wfMessage('wfarm-wiki-creation-date')}}</th>
            <th></th>
        </tr>
    @foreach($allWikiCreated as $row)
        <tr wiki-id="{{$row['id']}}" class="{{$row['wiki_status'] === 'IN_CREATION' ? "wfarm-in-creation": ""}}">
            <td>
                @if($row['wiki_status'] === 'CREATED')
                <a target="_blank" href="{{$baseURL}}/wiki{{$row['id']}}/mediawiki">{{$row['wiki_name']}}</a>
                @elseif($row['wiki_status'] === 'TO_BE_DELETED')
                {{$row['wiki_name']}} {{wfMessage('wfarm-wiki-to-be-deleted')}}
                @else
                {{$row['wiki_name']}} {{wfMessage('wfarm-wiki-in-creation')}}
                @endif
            </td>

            <td>{{$row['created_at']}} {{SpecialCreateWiki::within2Days($row['created_at']) && $row['wiki_status'] !== 'IN_CREATION' ? "(".wfMessage("wfarm-recent").")": ""}}</td>
            <td><?php
                $deleteButton = new ButtonWidget([
                    'classes' => ['wfarm-remove-wiki'],
                    'label' => wfMessage('wfarm-remove-wiki')->text(),
                    'flags' => ['primary', 'destructive'],
                    'infusable' => true
                ]);
                $deleteButton->setAttributes(['wiki-id' => $row['id']]);
                echo $deleteButton;
                ?></td>
        </tr>
    @endforeach
</table>
@if (count($allWikiCreated) === 0)
<p>{{wfMessage('wfarm-no-wikis-found')}}</p>
@endif
</div>