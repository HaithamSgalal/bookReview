<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

class Book extends Model
{
    use HasFactory;

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function scopeTitle(Builder $query, string $title): builder|QueryBuilder
    {
        return $query->where("title", "like", "%" . $title . "%");
    }

    private function dateRangeFilter(Builder $query, string $from = null, string $to = null)

    {
        if ($from && !$to) {
            $query->where('created_at', ">=", "$from");
        } elseif (!$from && $to) {
            $query->where("created_at", "<=", "$to");
        } elseif ($from && $to) {
            $query->whereBetween("created_at", [$from, $to]);
        };
    }

    public function scopeWithReviewsCount(Builder $query, $from = null, $to = null): Builder|QueryBuilder
    {
        return $query->withCount([
            'reviews' => fn (builder $q) => $this->dateRangeFilter($q, $from, $to)

        ]);
    }

    public function scopeWithAvgRating(Builder $query, $from = null, $to = null): Builder|QueryBuilder
    {
        return $query->withAvg([
            'reviews' => fn (builder $q) => $this->dateRangeFilter($q, $from, $to)
        ], 'rating');
    }

    public function scopePopular(Builder $query, $from = null, $to = null): Builder|QueryBuilder
    {
        return $query->WithReviewsCount()
            ->orderBy('reviews_count', 'desc');
    }

    public function scopeHighestRated(Builder $query, $from = null, $to = null): Builder |QueryBuilder
    {
        return $query->withAvgRating()
            ->orderBy('reviews_avg_rating', 'desc');
    }

    public function scopeMinReviews(Builder $query, $minReviews)
    {
        return $query->having('Reviews_count', '>=', $minReviews);
    }

 


    public function scopePopularLastMonth(Builder $query): Builder |QueryBuilder
    {
        return $query->Popular(now()->subMonth(), now())
            ->HighestRated(now()->subMonth(), now())
            ->minReviews(2);
    }

    public function scopePopularLastSixMonths(Builder $query): Builder |QueryBuilder
    {
        return $query->Popular(now()->subMonths(6), now())
            ->HighestRated(now()->subMonths(6), now())
            ->minReviews(5);
    }

    public function scopeHighestRatedLastMonth(Builder $query): Builder |QueryBuilder
    {
        return $query
            ->HighestRated(now()->subMonth(), now())
            ->Popular(now()->subMonth(), now())
            ->minReviews(2);
    }


    public function scopeHighestRatedLastSixMonths(Builder $query): Builder |QueryBuilder
    {
        return $query
            ->HighestRated(now()->subMonths(6), now())
            ->Popular(now()->subMonths(6), now())
            ->minReviews(2);
    }


    protected static function booted()
    {
       static::created(fn (Book $book) => cache()->forget('book:' . $book->id));
       static::updated(fn (Book $book) => cache()->forget('book:' . $book->id)); 
       static::deleted(fn (Book $book) => cache()->forget('book:' . $book->id)); 
    } 
}
