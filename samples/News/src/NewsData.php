<?php

namespace Samples\News;

use DateTimeImmutable;

class NewsData
{
    /**
     * Locate an article by its slug across the featured + list. Returns null
     * for unknown slugs so the router can fall back to the list view.
     */
    public static function findBySlug(string $slug): ?Article
    {
        $featured = self::featured();
        if ($featured->slug() === $slug) {
            return $featured;
        }
        foreach (self::articles() as $article) {
            if ($article->slug() === $slug) {
                return $article;
            }
        }
        return null;
    }

    public static function featured(): Article
    {
        return new Article(
            category: 'Banking',
            title: 'Discovery Bank breaks records with new AI banking platform',
            date: new DateTimeImmutable('2026-05-22'),
            coverImage: 'https://picsum.photos/seed/discovery-bank/1200/600',
            excerpt: 'Discovery Bank\'s new AI-driven platform has attracted over 500,000 new customers in its first month — and is on track to triple that by year end. The platform offers real-time investment advice, automated budgeting, and crypto custody.',
            content: 'Discovery Bank has launched its long-anticipated AI banking platform, which the company says is the most advanced in South Africa. The platform offers real-time investment recommendations powered by an in-house language model trained on local market data, plus automated budgeting, FX hedging, and a regulated crypto custody service. Early adoption has exceeded internal forecasts by a wide margin.',
        );
    }

    /** @return Article[] */
    public static function articles(): array
    {
        return [
            new Article(
                category: 'Mobile',
                title: 'MTN South Africa unveils next-generation 6G rollout strategy',
                date: new DateTimeImmutable('2026-05-22'),
                coverImage: 'https://picsum.photos/seed/mtn-6g/600/400',
                excerpt: 'MTN has set out a five-year roadmap to commercial 6G, with the first urban pilots expected in Johannesburg and Cape Town by mid-2027.',
                content: 'MTN South Africa says it will spend R12 billion over five years on a phased 6G rollout, starting with infrastructure densification in 2026 and commercial pilots in 2027.',
            ),
            new Article(
                category: 'Internet',
                title: 'Vodacom expands rural fibre rollout to 50,000 more households',
                date: new DateTimeImmutable('2026-05-21'),
                coverImage: 'https://picsum.photos/seed/vodacom-fibre/600/400',
                excerpt: 'A new R1.8 billion commitment will bring affordable fibre to underserved areas across the Eastern Cape, KZN, and Limpopo over the next 18 months.',
                content: 'The expansion is part of a broader public-private digital inclusion drive backed by the Department of Communications.',
            ),
            new Article(
                category: 'Cybersecurity',
                title: 'Quantum-resistant encryption now mandatory for SA banks',
                date: new DateTimeImmutable('2026-05-21'),
                coverImage: 'https://picsum.photos/seed/quantum-crypto/600/400',
                excerpt: 'The Reserve Bank has updated its prudential standards to require post-quantum cryptography for all customer-facing systems by Q1 2027.',
                content: 'The directive applies to all registered banks and is the first of its kind on the continent.',
            ),
            new Article(
                category: 'Cloud',
                title: 'Microsoft Azure expands South African data centre footprint',
                date: new DateTimeImmutable('2026-05-20'),
                coverImage: 'https://picsum.photos/seed/azure-sa/600/400',
                excerpt: 'A third availability zone in Johannesburg North goes live this week, bringing additional latency-sensitive workloads in scope for local hosting.',
                content: 'The new zone adds 250 MW of compute capacity and is powered entirely by renewable sources.',
            ),
            new Article(
                category: 'Business',
                title: 'Cell C completes restructuring and returns to profitability',
                date: new DateTimeImmutable('2026-05-20'),
                coverImage: 'https://picsum.photos/seed/cellc/600/400',
                excerpt: 'After four years of restructuring, Cell C has reported its first profitable half-year — a key milestone before a possible JSE re-listing in 2027.',
                content: 'The company credits its tower-sharing deal with MTN and a leaner enterprise division for the turnaround.',
            ),
            new Article(
                category: 'Cellular',
                title: 'Rain launches satellite-backed coverage for remote farms',
                date: new DateTimeImmutable('2026-05-19'),
                coverImage: 'https://picsum.photos/seed/rain-sat/600/400',
                excerpt: 'A partnership with a low-earth-orbit satellite operator means Rain can now offer broadband to farms outside its terrestrial 5G footprint.',
                content: 'The hybrid service uses terrestrial 5G where available and falls back to satellite seamlessly.',
            ),
            new Article(
                category: 'Cybersecurity',
                title: 'SARS warns of sophisticated tax-season phishing campaign',
                date: new DateTimeImmutable('2026-05-19'),
                coverImage: 'https://picsum.photos/seed/sars-phish/600/400',
                excerpt: 'Attackers are using realistic eFiling clones and SMS lures to steal credentials ahead of the 2026 filing season opening on 1 July.',
                content: 'SARS has published a list of known fraudulent domains and urged taxpayers to verify URLs before logging in.',
            ),
            new Article(
                category: 'Banking',
                title: 'Standard Bank deploys autonomous financial advisors at 200 branches',
                date: new DateTimeImmutable('2026-05-18'),
                coverImage: 'https://picsum.photos/seed/stdbank-ai/600/400',
                excerpt: 'The roll-out is the first large-scale deployment of agentic AI in a South African bank — handling everything from loan applications to retirement planning.',
                content: 'A human advisor remains available on request, and all autonomous decisions are reviewable.',
            ),
        ];
    }

    /** @return WhatsNextItem[] */
    public static function whatsNext(): array
    {
        return [
            new WhatsNextItem(
                category: 'Government',
                title: 'New digital ID rollout schedule announced',
                coverImage: 'https://picsum.photos/seed/digital-id/400/240',
            ),
            new WhatsNextItem(
                category: 'Startups',
                title: 'Top 5 fintech startups to watch in 2026',
                coverImage: 'https://picsum.photos/seed/fintech-watch/400/240',
            ),
            new WhatsNextItem(
                category: 'Telecoms',
                title: 'Telkom restructures fibre wholesale division',
                coverImage: 'https://picsum.photos/seed/telkom-fibre/400/240',
            ),
            new WhatsNextItem(
                category: 'Funding',
                title: 'South African startups raise R8 billion this year',
                coverImage: 'https://picsum.photos/seed/sa-funding/400/240',
            ),
            new WhatsNextItem(
                category: 'AI',
                title: 'OpenAI vs Google: enterprise AI showdown in 2026',
                coverImage: 'https://picsum.photos/seed/ai-showdown/400/240',
            ),
            new WhatsNextItem(
                category: 'Energy',
                title: 'Eskom hits 100% renewable milestone in grid stability',
                coverImage: 'https://picsum.photos/seed/eskom-renew/400/240',
            ),
        ];
    }

    /** @return IndustryNewsItem[] */
    public static function industryNews(): array
    {
        return [
            new IndustryNewsItem('Reserve Bank cuts repo rate to 6.5%', new DateTimeImmutable('2026-05-22')),
            new IndustryNewsItem('JSE launches new tech-focused index', new DateTimeImmutable('2026-05-22')),
            new IndustryNewsItem('Nedbank reports Q1 record digital transactions', new DateTimeImmutable('2026-05-21')),
            new IndustryNewsItem('Pick n Pay introduces drone delivery in Pretoria', new DateTimeImmutable('2026-05-21')),
            new IndustryNewsItem('Capitec hits 25 million customers milestone', new DateTimeImmutable('2026-05-20')),
            new IndustryNewsItem('Amazon expands Cape Town logistics hub', new DateTimeImmutable('2026-05-20')),
            new IndustryNewsItem('Sanlam acquires Hollard insurance assets', new DateTimeImmutable('2026-05-19')),
            new IndustryNewsItem('MultiChoice prepares for Disney+ integration', new DateTimeImmutable('2026-05-19')),
            new IndustryNewsItem('FNB launches first AI mortgage advisor', new DateTimeImmutable('2026-05-18')),
            new IndustryNewsItem('Liberty Group launches crypto custody service', new DateTimeImmutable('2026-05-18')),
        ];
    }
}
