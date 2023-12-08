<?php

namespace OhDear\OhDearPulse\Livewire;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Livewire\Attributes\Lazy;
use Laravel\Pulse\Livewire\Card;
use OhDear\PhpSdk\Resources\Site;
use OhDear\PhpSdk\Resources\Check;
use OhDear\OhDearPulse\OhDearPulse;
use Illuminate\Contracts\Support\Renderable;
use OhDear\OhDearPulse\Livewire\Concerns\UsesOhDearApi;
use OhDear\OhDearPulse\Livewire\Concerns\RemembersApiCalls;

#[Lazy]
class OhDearUptimePulseCardComponent extends Card
{
    use RemembersApiCalls;
    use UsesOhDearApi;

    public int $siteId;

    protected function css()
    {
        return __DIR__.'/../../dist/output.css';
    }

    public function mount(?int $siteId = null)
    {
        $this->sites = collect();

        $this->siteId = $siteId ?? config('services.oh_dear.pulse.site_id');
    }

    public function getData() {
        return collect([
            [
                now()->timestamp * 1000,
                70,
            ],
            [
                now()->addMinutes(-1)->timestamp * 1000,
                80,
            ],
            [
                now()->addMinutes(-2)->timestamp * 1000,
                95,
            ],
            [
                now()->addMinutes(-3)->timestamp * 1000,
                75,
            ],
            [
                now()->addMinutes(-4)->timestamp * 1000,
                75,
            ],
            [
                now()->addMinutes(-5)->timestamp * 1000,
                80,
            ],
            [
                now()->addMinutes(-6)->timestamp * 1000,
                90,
            ],
            [
                now()->addMinutes(-7)->timestamp * 1000,
                85,
            ],
            [
                now()->addMinutes(-8)->timestamp * 1000,
                80,
            ],
            [
                now()->addMinutes(-9)->timestamp * 1000,
                60,
            ],
            [
                now()->addMinutes(-10)->timestamp * 1000,
                75,
            ],
        ])->toArray();
    }

    protected function getLabels(): array
    {

        return collect($this->getData())
                ->map(function (array $dataPoint) {
                    return Carbon::createFromTimestamp($dataPoint[0]/1000)
                        ->format('Y-m-d H:i');
                })
                ->toArray();
    }

    public function render(): Renderable
    {
        $site = $this->getSite();

        return view('ohdear-pulse::uptime', [
            'site' => $site,
            'status' => $this->getStatus($site),
            'statusColor' => $this->getStatusColor(),
            'performance' => $this->getPerformance($site),
        ]);
    }

    public function getSite(): ?Site
    {
        if (! OhDearPulse::isConfigured()) {
            return null;
        }

        $siteAttributes = $this->remember(
            fn () => $this->ohDear()?->site($this->siteId)?->attributes,
            'site:'.$this->siteId,
            CarbonInterval::seconds(10),
        );

        return new Site($siteAttributes);
    }

    protected function getStatus(?Site $site): ?string
    {
        if (! $site) {
            return null;
        }

        if (! $check = $this->getCheck($site, 'uptime')) {
            return null;
        }

        return match($check->summary) {
            'Up' => 'Online',
            default => $check->summary,
        };
    }

    protected function getStatusColor()
    {
        return match($this->getStatus($this->getSite())) {
            'Online' => 'bg-emerald-500',
            default => 'bg-gray-600',
        };
    }

    protected function getPerformance(?Site $site): ?string
    {
        if (! $site) {
            return null;
        }

        if (! $check = $this->getCheck($site, 'performance')) {
            return null;
        }

        return $check->summary;
    }

    protected function getCheck(Site $site, string $type): ?Check
    {
        return collect($site->checks)
            ->first(fn (Check $check) => $check->type === $type);
    }
}
