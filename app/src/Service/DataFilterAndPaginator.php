<?php
namespace App\Service;

use DateTime;

class DataFilterAndPaginator
{
    public function filterAndSortData(array $data): array
    {
        $filteredData = array_filter($data, function ($entry) {
            if (!isset($entry['endDate'])) {
                // La clé 'endDate' n'existe pas, exclure cet élément
                return false;
            }
            if (!isset($entry['state']) || $entry['state'] !== 'Public') {
                // La clé 'state' n'existe pas, ou différent de Public exclure cet élément
                return false;
            }
            $endDate = DateTime::createFromFormat(DateTime::ISO8601, $entry['endDate']);
            $now = new DateTime();
            return $endDate > $now;
        });
    
        usort($filteredData, function ($a, $b) {
            $dateA = DateTime::createFromFormat(DateTime::ISO8601, $a['endDate']);
            $dateB = DateTime::createFromFormat(DateTime::ISO8601, $b['endDate']);
            return $dateA <=> $dateB;
        });
    
        return $filteredData;
    }

    public function filterMemberShipSortData(array $data): array
    {
        $filteredData = array_filter($data, function ($entry) {
            if (!isset($entry['endDate'])) {
                // La clé 'endDate' n'existe pas inclure cet élément
                return true;
            }
            $endDate = DateTime::createFromFormat(DateTime::ISO8601, $entry['endDate']);
            $now = new DateTime();
            return $endDate > $now;
        });
    
        usort($filteredData, function ($a, $b) {
            $dateA = DateTime::createFromFormat(DateTime::ISO8601, $a['endDate']);
            $dateB = DateTime::createFromFormat(DateTime::ISO8601, $b['endDate']);
            return $dateA <=> $dateB;
        });
    
        return $filteredData;
    }

    public function paginateData(array $data, int $page, int $itemsPerPage = 6): array
    {
        $totalItems = count($data);
        $totalPages = ceil($totalItems / $itemsPerPage);
        $start = ($page - 1) * $itemsPerPage;
        $pageItems = array_slice($data, $start, $itemsPerPage);

        return [
            'items' => $pageItems,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ];
    }
}