<?php

namespace AdminEshop\Contracts\Listing;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as PaginatorContract;
use Illuminate\Pagination\UrlWindow as BaseUrlWindow;

class UrlWindow extends BaseUrlWindow
{
    protected $fromEndings = 1;

    /**
     * Get the window of URLs to be shown.
     *
     * @return array
     */
    public function get()
    {
        $onEachSide = $this->paginator->onEachSide;

        if ($this->paginator->lastPage() < ($onEachSide * 2) + 4) {
            return $this->getSmallSlider();
        }

        return $this->getUrlSlider($onEachSide);
    }

    /**
     * Create a URL slider links.
     *
     * @param  int  $onEachSide
     * @return array
     */
    protected function getUrlSlider($onEachSide)
    {
        $window = $onEachSide + 1 + ($this->fromEndings * 2);

        if (! $this->hasPages()) {
            return ['first' => null, 'slider' => null, 'last' => null];
        }

        // If the current page is very close to the beginning of the page range, we will
        // just render the beginning of the page range, followed by the last 2 of the
        // links in this list, since we will not have room to create a full slider.
        if ($this->currentPage() <= $window) {
            return $this->getSliderTooCloseToBeginning($window, $onEachSide);
        }

        // If the current page is close to the ending of the page range we will just get
        // this first couple pages, followed by a larger window of these ending pages
        // since we're too close to the end of the list to create a full on slider.
        elseif ($this->currentPage() > ($this->lastPage() - $window)) {
            return $this->getSliderTooCloseToEnding($window, $onEachSide);
        }

        // If we have enough room on both sides of the current page to build a slider we
        // will surround it with both the beginning and ending caps, with this window
        // of pages in the middle providing a Google style sliding paginator setup.
        return $this->getFullSlider($onEachSide);
    }

    /**
     * Get the slider of URLs when too close to the beginning of the window.
     *
     * @param  int  $window
     * @param  int  $onEachSide
     * @return array
     */
    protected function getSliderTooCloseToBeginning($window, $onEachSide)
    {
        return [
            'first' => $this->paginator->getUrlRange(1, $this->currentPage() + $onEachSide),
            'slider' => null,
            'last' => $this->getFinish(),
        ];
    }

    /**
     * Get the slider of URLs when too close to the ending of the window.
     *
     * @param  int  $window
     * @param  int  $onEachSide
     * @return array
     */
    protected function getSliderTooCloseToEnding($window, $onEachSide)
    {
        $last = $this->paginator->getUrlRange(
            $this->currentPage() - $onEachSide,
            $this->lastPage()
        );

        return [
            'first' => $this->getStart(),
            'slider' => null,
            'last' => $last,
        ];
    }

    /**
     * Get the starting URLs of a pagination slider.
     *
     * @return array
     */
    public function getStart()
    {
        return $this->paginator->getUrlRange(1, 0 + $this->fromEndings);
    }

    /**
     * Get the ending URLs of a pagination slider.
     *
     * @return array
     */
    public function getFinish()
    {
        return $this->paginator->getUrlRange(
            $this->lastPage() - ($this->fromEndings - 1),
            $this->lastPage()
        );
    }
}
