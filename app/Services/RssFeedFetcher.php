<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;
use Throwable;

final class RssFeedFetcher
{
    /**
     * @return array<int, array{title: string, url: string, published_at: Carbon|null, thumbnail: string|null}>
     */
    public function fetch(string $url): array
    {
        $response = Http::timeout(15)->get($url);

        if (! $response->successful()) {
            return [];
        }

        $body = $response->body();
        $xml = @simplexml_load_string($body);

        if ($xml === false) {
            return [];
        }

        $items = [];
        $channel = $xml->channel ?? $xml;

        foreach ($channel->item ?? [] as $item) {
            $title = (string) ($item->title ?? '');
            $link = (string) ($item->link ?? $item->guid ?? '');
            $pubDate = property_exists($item, 'pubDate') && $item->pubDate !== null ? $this->parsePubDate((string) $item->pubDate) : null;
            $thumbnail = $this->extractThumbnail($item);

            $items[] = [
                'title' => $title,
                'url' => $link,
                'published_at' => $pubDate,
                'thumbnail' => $thumbnail,
            ];
        }

        return $items;
    }

    private function parsePubDate(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            return Date::parse($value);
        } catch (Throwable) { // @codeCoverageIgnore
            return null; // @codeCoverageIgnore
        } // @codeCoverageIgnore
    }

    private function extractThumbnail(SimpleXMLElement $item): ?string
    {
        if (property_exists($item->children('media', true), 'content') && $item->children('media', true)->content !== null) {
            $content = $item->children('media', true)->content;
            $attrs = $content->attributes();
            if (isset($attrs['url'])) {
                return (string) $attrs['url'];
            }
        }

        if (property_exists($item, 'enclosure') && $item->enclosure !== null) {
            $enc = $item->enclosure;
            $type = (string) ($enc['type'] ?? '');
            if (str_starts_with($type, 'image/')) {
                return (string) ($enc['url'] ?? '');
            }
        }

        return null;
    }
}
