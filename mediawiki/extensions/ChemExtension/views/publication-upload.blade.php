<ul>
@foreach($uploadedFiles as $name => $path)
        <li>Uploaded file: <span>{{$name}}: {{$f}}</span></li>
@endforeach
</ul>
<a href="{{$wikiUrl}}/Special:PublicationImportSpecialpage">Go back to import page</a>