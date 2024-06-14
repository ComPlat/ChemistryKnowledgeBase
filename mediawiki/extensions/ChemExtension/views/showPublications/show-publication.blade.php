@if ($orcid != '')
    <h2>These publications are assigned to the author's ORCID</h2>
    @if ($orcidPublications == '' || $orcidPublications == '-no-publications-yet--')
        <p>There are no publications for this ORCID</p>
    @else
    {!! $orcidPublications !!}
    @endif
@endif

<h2>These publications are from authors with the name "{{$name}}"</h2>
@if ($authorPublications == '--no-publications-yet--')
    <p>There are no publications for this author</p>
@else
{!! $authorPublications !!}
@endif
