<?php

namespace App\Support;

/**
 * Shared client/lead detail tab slug resolution (URL path, ?tab=, aliases).
 */
final class ClientDetailTab
{
    public const DEFAULT = 'activities';

    /**
     * @return array{tab: string, slug: string}
     */
    public static function resolve(?string $forcedTab, mixed $routeTab = null, mixed $queryTab = null): array
    {
        $allowedTabs = [
            'activities',
            'noteterm',
            'application',
            'alldocuments',
            'notuseddocuments',
            'accounts',
            'email-v2',
        ];
        $tabAliases = [
            'notestrm' => 'noteterm',
            'documents' => 'alldocuments',
            'migrationdocuments' => 'alldocuments',
        ];
        $allowedSlugs = array_unique(array_merge($allowedTabs, array_keys($tabAliases)));
        $requestedTab = $forcedTab ?: ($routeTab ?? $queryTab);
        $requestedTab = is_string($requestedTab) ? $requestedTab : null;

        if (in_array($requestedTab, ['documents', 'migrationdocuments'], true)) {
            $requestedTab = 'alldocuments';
        }
        if ($requestedTab === null || $requestedTab === '' || ! in_array($requestedTab, $allowedSlugs, true)) {
            $requestedTab = self::DEFAULT;
        }

        $activeTab = $tabAliases[$requestedTab] ?? $requestedTab;
        $activeTabSlug = array_search($activeTab, $tabAliases, true);
        if ($activeTabSlug === false) {
            $activeTabSlug = $requestedTab;
        }
        if ($activeTab === 'alldocuments' && in_array($activeTabSlug, ['documents', 'migrationdocuments'], true)) {
            $activeTabSlug = 'alldocuments';
        }

        return ['tab' => $activeTab, 'slug' => $activeTabSlug];
    }
}
