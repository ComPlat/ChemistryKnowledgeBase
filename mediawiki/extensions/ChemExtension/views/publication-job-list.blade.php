<h2>Currently queued jobs for import</h2>
@if(count($jobs) === 0)
    <span>none</span>
@else
<ul>
    @foreach($jobs as $job)
                <li><a target="_blank" href="{{$job->getTitle()->getFullUrl()}}">{{$job->getTitle()->getText()}}</a></li>
    @endforeach
</ul>
@endif