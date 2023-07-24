<h2>Investigations</h2>
<ul>
@foreach($list as $i)
  <li><a href="{{$i['title']->getFullURL()}}">{{$i['title']->getSubpageText()}}</a></li>
@endforeach
</ul>