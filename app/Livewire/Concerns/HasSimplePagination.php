<?php

namespace App\Livewire\Concerns;

trait HasSimplePagination
{
    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function nextPage(): void
    {
        if ($this->page < $this->totalPages) {
            $this->page++;
        }
    }

    public function getTotalPagesProperty(): int
    {
        return max(1, (int) ceil($this->paginationSourceCount() / $this->perPage));
    }

    public function getShowingFromProperty(): int
    {
        if ($this->paginationSourceCount() === 0) {
            return 0;
        }

        return (($this->page - 1) * $this->perPage) + 1;
    }

    public function getShowingToProperty(): int
    {
        if ($this->paginationSourceCount() === 0) {
            return 0;
        }

        return min($this->page * $this->perPage, $this->paginationSourceCount());
    }

    abstract protected function paginationSourceCount(): int;
}
