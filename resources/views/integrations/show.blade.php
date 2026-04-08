@extends(app('app.layout'))

@section('page-title', \Iquesters\Foundation\Helpers\MetaHelper::make([($integration->name ?? 'Integration'), 'Integration']))
@section('meta-description', \Iquesters\Foundation\Helpers\MetaHelper::description('Show page of Integration'))

@php
    $tabs = [
        [
            'route' => 'integration.show',
            'params' => [
                'integrationUid' => $integration->uid,
            ],
            'icon' => 'far fa-fw fa-list-alt',
            'label' => 'Overview',
            // 'permission' => 'view-organisations',
        ],
        [
            'route' => 'integration.configure',
            'params' => [
                'integrationUid' => $integration->uid,
            ],
            'icon' => 'fas fa-fw fa-sliders-h',
            'label' => 'Configure',
            // 'permission' => 'view-organisations-users',
        ],
        [
            'route' => 'integration.apiconf',
            'params' => [
                'integrationUid' => $integration->uid,
            ],
            'icon' => 'fas fa-fw fa-screwdriver-wrench',
            'label' => 'Api Conf',
            // 'permission' => 'view-teams'
        ],
        [
            'route' => 'integration.knob',
            'params' => [
                'integrationUid' => $integration->uid,
            ],
            'icon' => 'fas fa-fw fa-sliders',
            'label' => 'Knob',
            // 'permission' => 'view-teams'
        ],
        [
            'route' => 'integration.syncdata',
            'params' => [
                'integrationUid' => $integration->uid,
            ],
            'icon' => 'fas fa-fw fa-rotate',
            'label' => 'Sync Data',
            // 'permission' => 'view-teams'
        ],
    ];
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center justify-content-start gap-2">
            <h5 class="fs-6 text-muted mb-0">
                {{ $integration->name }}
                {!! $integration->supportedInt?->getMeta('icon') !!}
            </h5>
            <x-userinterface::status :status="$integration->status" />
        </div>

        <div class="d-flex align-items-center justify-content-center gap-2">
            @if ($integration->status !== 'deleted')
                <a class="btn btn-sm btn-outline-dark" href="{{ route('integration.edit', $integration->uid) }}">
                    <i class="fas fa-fw fa-edit"></i>
                    <span class="d-none d-md-inline-block ms-1">Edit</span>
                </a>
                <form action="{{ route('integration.destroy', $integration->uid) }}" 
                    method="POST" 
                    onsubmit="return confirm('Are you sure?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-fw fa-trash"></i>
                        <span class="d-none d-md-inline-block ms-1">Delete</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    @php
        use Illuminate\Support\Str;
    @endphp

    @foreach($integration->metas as $meta)

        @if($meta->meta_key === 'chatbot_vector')
            @continue
        @endif

        @php
            $isUrl    = $meta->meta_key === 'url';
            $isSecret = Str::contains($meta->meta_key, ['token', 'key', 'secret']);

            $displayValue = $isSecret
                ? Str::mask($meta->meta_value, '*', 0, max(strlen($meta->meta_value) - 4, 0))
                : $meta->meta_value;
        @endphp

        <div class="d-flex align-items-center justify-content-start gap-2">
            <div class="text-muted text-nowrap">
                {{ Str::headline($meta->meta_key) }} :
            </div>

            <div class="text-break" id="meta-{{ $meta->id }}">
                <code>{{ $displayValue }}</code>
            </div>

            @if($isUrl)
                <i class="fas fa-copy text-muted copy-icon ms-2"
                onclick="copyText('meta-{{ $meta->id }}', this)"
                title="Copy URL"></i>
            @endif
        </div>

    @endforeach
    
    <div>
        Channel section
    </div>
@endsection