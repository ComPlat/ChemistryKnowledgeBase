<p>This page show recent changes of molecules via the special Page <a href="{{"$wgScriptPath/index.php/Special:ModifyMolecule"}}">Special:ModifyMolecule</a></p>
{!! $form !!}
<p>
@if (count($moleculeLog) === 0)
    No log found!
@else
<table id="molecule-log-table" width="100%">
    <tr>
        <th>Title</th>
        <th>Timestamp</th>
        <th>replaced chemform-tag</th>
        <th>replaced moleculelink</th>
        <th>no change because only molecule ID referenced</th>
    </tr>
@foreach($moleculeLog as $entry)
<tr>
    <td><a href="{{$entry['title']->getFullURL()}}">{{$entry['title']->isSubpage() ? $entry['title']->getSubpageText() : $entry['title']->getText()}}</a></td>
    <td>{{date('Y-m-d H:i:s', $entry['timestamp'])}}</td>
    <td>{{$entry['replacedChemForm'] ? 'yes' : 'no'}}</td>
    <td>{{$entry['replacedChemFormLink'] ? 'yes' : 'no'}}</td>
    <td>{{$entry['onlyMoleculeId'] ? 'yes' : 'no'}}</td>
</tr>
@endforeach
</table>
@endif
</p>