<div><span class="chem-extension-literature-title">{{$title}}</span>
@foreach($authors as $author)
        <span class="chem-extension-literature-author">{{$author}}</span>,
@endforeach
<span class="chem-extension-literature-year">{{$year}}</span>
</div>