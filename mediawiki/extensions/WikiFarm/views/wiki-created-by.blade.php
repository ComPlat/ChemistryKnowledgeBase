<p>Wikis created by you</p>
<table style="width: 80%">
    @foreach($allWikiCreated as $row)
        <tr>
            <td>{{$row['id']}}</td>
            <td>{{$row['wiki_name']}}</td>
        </tr>
    @endforeach
</table>