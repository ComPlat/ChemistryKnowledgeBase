<h2>Molecule is used on following pages</h2>
<ul>
    @foreach($pages as $p)
        <li><a target="_blank" href="{{$p->getFullURL()}}">{{$p->getText()}}</a></li>
    @endforeach
</ul>