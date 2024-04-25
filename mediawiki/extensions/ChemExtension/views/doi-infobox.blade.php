<div>
    <table class="infobox wikitable" style="font-size:90%; margin-top:0; margin-bottom:0;width:410px; max-width:500px;">
        <tr>
            <th colspan="2" style="font-size:120%; text-align:center; cursor: pointer;">About</th>
        </tr>
    </table>
    <table class="wikitable" style="font-size:90%; margin-top:0; margin-bottom:0; width:410px; max-width:500px;">
        <tr style="display: none;">
            <td>DOI</td>
            <td><a href="https://dx.doi.org/{{$doi}}">{{$doi}}</a></td>
        </tr>
        <tr style="display: none;">
            <td>Authors</td>
            <td>
                @foreach($authors as $a)
                    <a href="{{$wgScriptPath}}/Special:ShowPublications?orcid={{$a['orcidUrl']}}">{{$a['name']}}</a>,
                @endforeach
            </td>
        </tr>
        <tr style="display: none;">
            <td>Submitted</td>
            <td>{{$submittedAt}}</td>
        </tr>
        @if($publishedOnlineAt !== '')
            <tr style="display: none;">
                <td>Published online</td>
                <td>{{$publishedOnlineAt}}</td>
            </tr>
        @endif
        @if($licenses !== '')
            <tr style="display: none;">
                <td>Licenses</td>
                <td>
                    @foreach($licenses as $license)
                        <a href="{{$license['URL']}}">{{$license['URL']}}</a>,
                @endforeach
            </tr>
        @endif
        @if($subjects !== '')
            <tr style="display: none;">
                <td>Subjects</td>
                <td>{{implode(", ", $subjects)}}</td>
            </tr>
        @endif
        <tr style="display: none;">
            <td colspan="2" style="font-size:80%; text-align:center; "><a
                        href="{{$wgScriptPath}}/Special:Literature?doi={{$doi}}">Go to literature page</td>
        </tr>
    </table>
</div>