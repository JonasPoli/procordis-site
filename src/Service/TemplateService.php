<?php

namespace App\Service;

use App\Entity\GlobalTags;
use App\Entity\PageSeo;
use App\Entity\GeneralData;
use App\Repository\GeneralDataRepository;
use App\Repository\GlobalTagsRepository;
use App\Repository\PageSeoRepository;

class TemplateService
{
    public function __construct(
        private GlobalTagsRepository $globalTagsRepository,
        private PageSeoRepository $pageSeoRepository,
        private GeneralDataRepository $generalDataRepository
    ) {
    }

    public function getGlobalTags(): ?GlobalTags
    {
        return $this->globalTagsRepository->findOneBy([]) ?? new GlobalTags();
    }

    public function getPageSeo(): ?PageSeo
    {
        return $this->pageSeoRepository->findOneBy([]) ?? new PageSeo();
    }

    public function getGeneralData(): ?GeneralData
    {
        return $this->generalDataRepository->findOneBy([]) ?? new GeneralData();
    }
}
