-- ============================================================
-- Flixarion — PostgreSQL 15+ Database Schema
-- 18 tables · 6 domains
-- ============================================================

BEGIN;

-- ──────────────────────────────────────────────────────────────
-- Domain 1: Users & Auth
-- ──────────────────────────────────────────────────────────────

CREATE TABLE users (
    id              BIGSERIAL       PRIMARY KEY,
    name            VARCHAR(255)    NOT NULL,
    email           VARCHAR(255)    NOT NULL UNIQUE,
    email_verified_at TIMESTAMP     NULL,
    password        VARCHAR(255)    NOT NULL,
    role            VARCHAR(50)     NOT NULL DEFAULT 'user',
    is_banned       BOOLEAN         NOT NULL DEFAULT FALSE,
    remember_token  VARCHAR(100)    NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP       NOT NULL DEFAULT NOW()
);

CREATE TABLE personal_access_tokens (
    id              BIGSERIAL       PRIMARY KEY,
    tokenable_type  VARCHAR(255)    NOT NULL,
    tokenable_id    BIGINT          NOT NULL,
    name            VARCHAR(255)    NOT NULL,
    token           VARCHAR(64)     NOT NULL UNIQUE,
    abilities       TEXT            NULL,
    expires_at      TIMESTAMP       NULL,
    last_used_at    TIMESTAMP       NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP       NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_pat_tokenable
    ON personal_access_tokens (tokenable_type, tokenable_id);

-- ──────────────────────────────────────────────────────────────
-- Domain 2: Content & Genres
-- ──────────────────────────────────────────────────────────────

CREATE TABLE contents (
    id                  BIGSERIAL       PRIMARY KEY,
    tmdb_id             BIGINT          NOT NULL UNIQUE,
    imdb_id             VARCHAR(20)     NULL,
    type                VARCHAR(50)     NOT NULL,
    title               VARCHAR(500)    NOT NULL,
    original_title      VARCHAR(500)    NULL,
    year                INTEGER         NULL,
    description         TEXT            NULL,
    poster_path         VARCHAR(500)    NULL,
    backdrop_path       VARCHAR(500)    NULL,
    "cast"              JSONB           NULL,
    director            VARCHAR(255)    NULL,
    rating              DECIMAL(3,1)    NULL,
    vote_count          INTEGER         NULL,
    runtime             INTEGER         NULL,
    trailer_url         VARCHAR(500)    NULL,
    alternative_titles  JSONB           NULL,
    language            VARCHAR(10)     NULL,
    status              VARCHAR(50)     NULL,
    enrichment_status   VARCHAR(50)     NOT NULL DEFAULT 'pending',
    confidence_score    DECIMAL(5,2)    NULL,
    is_published        BOOLEAN         NOT NULL DEFAULT FALSE,
    is_featured         BOOLEAN         NOT NULL DEFAULT FALSE,
    watch_count         INTEGER         NOT NULL DEFAULT 0,
    created_at          TIMESTAMP       NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP       NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_contents_type              ON contents (type);
CREATE INDEX idx_contents_year              ON contents (year);
CREATE INDEX idx_contents_rating            ON contents (rating);
CREATE INDEX idx_contents_title             ON contents (title);
CREATE INDEX idx_contents_enrichment_status ON contents (enrichment_status);
CREATE INDEX idx_contents_imdb_id           ON contents (imdb_id);
CREATE INDEX idx_contents_alt_titles        ON contents USING GIN (alternative_titles);
CREATE INDEX idx_contents_cast              ON contents USING GIN ("cast");

CREATE TABLE genres (
    id          BIGSERIAL       PRIMARY KEY,
    name        VARCHAR(100)    NOT NULL UNIQUE,
    slug        VARCHAR(100)    NOT NULL UNIQUE,
    tmdb_id     INTEGER         NULL UNIQUE,
    created_at  TIMESTAMP       NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP       NOT NULL DEFAULT NOW()
);

-- Seed genres
INSERT INTO genres (name, slug, tmdb_id) VALUES
    ('Action',          'action',           28),
    ('Adventure',       'adventure',        12),
    ('Animation',       'animation',        16),
    ('Comedy',          'comedy',           35),
    ('Crime',           'crime',            80),
    ('Documentary',     'documentary',      99),
    ('Drama',           'drama',            18),
    ('Family',          'family',           10751),
    ('Fantasy',         'fantasy',          14),
    ('History',         'history',          36),
    ('Horror',          'horror',           27),
    ('Music',           'music',            10402),
    ('Mystery',         'mystery',          9648),
    ('Romance',         'romance',          10749),
    ('Science Fiction', 'sci-fi',           878),
    ('Thriller',        'thriller',         53),
    ('TV Movie',        'tv-movie',         10770),
    ('War',             'war',              10752),
    ('Western',         'western',          37);

CREATE TABLE content_genre (
    content_id  BIGINT      NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
    genre_id    BIGINT      NOT NULL REFERENCES genres(id) ON DELETE CASCADE,
    created_at  TIMESTAMP   NOT NULL DEFAULT NOW(),
    PRIMARY KEY (content_id, genre_id)
);

CREATE INDEX idx_content_genre_genre ON content_genre (genre_id);

-- ──────────────────────────────────────────────────────────────
-- Domain 3: Content Hierarchy (TV Series)
-- ──────────────────────────────────────────────────────────────

CREATE TABLE seasons (
    id              BIGSERIAL       PRIMARY KEY,
    content_id      BIGINT          NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
    season_number   INTEGER         NOT NULL,
    tmdb_season_id  BIGINT          NULL,
    title           VARCHAR(255)    NULL,
    poster_path     VARCHAR(500)    NULL,
    overview        TEXT            NULL,
    episode_count   INTEGER         NULL,
    air_date        DATE            NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP       NOT NULL DEFAULT NOW(),
    UNIQUE (content_id, season_number)
);

CREATE INDEX idx_seasons_content ON seasons (content_id);

CREATE TABLE episodes (
    id                  BIGSERIAL       PRIMARY KEY,
    season_id           BIGINT          NOT NULL REFERENCES seasons(id) ON DELETE CASCADE,
    content_id          BIGINT          NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
    episode_number      INTEGER         NOT NULL,
    title               VARCHAR(255)    NULL,
    tmdb_episode_id     BIGINT          NULL,
    overview            TEXT            NULL,
    still_path          VARCHAR(500)    NULL,
    runtime             INTEGER         NULL,
    air_date            DATE            NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP       NOT NULL DEFAULT NOW(),
    UNIQUE (season_id, episode_number)
);

CREATE INDEX idx_episodes_content ON episodes (content_id);

-- ──────────────────────────────────────────────────────────────
-- Domain 4: Sources & Scanning
-- ──────────────────────────────────────────────────────────────

CREATE TABLE sources (
    id              BIGSERIAL       PRIMARY KEY,
    name            VARCHAR(255)    NOT NULL UNIQUE,
    base_url        VARCHAR(500)    NOT NULL,
    scraper_type    VARCHAR(100)    NOT NULL,
    config          JSONB           NULL,
    is_active       BOOLEAN         NOT NULL DEFAULT TRUE,
    health_score    DECIMAL(5,2)    NOT NULL DEFAULT 100.00,
    priority        INTEGER         NOT NULL DEFAULT 0,
    last_scan_at    TIMESTAMP       NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP       NOT NULL DEFAULT NOW()
);

CREATE TABLE source_links (
    id              BIGSERIAL       PRIMARY KEY,
    linkable_type   VARCHAR(50)     NOT NULL,
    linkable_id     BIGINT          NOT NULL,
    source_id       BIGINT          NOT NULL REFERENCES sources(id) ON DELETE CASCADE,
    file_path       VARCHAR(2048)   NOT NULL,
    quality         VARCHAR(50)     NULL,
    file_size       BIGINT          NULL,
    codec_info      VARCHAR(100)    NULL,
    part_number     INTEGER         NULL,
    subtitle_paths  JSONB           NULL,
    status          VARCHAR(50)     NOT NULL DEFAULT 'pending',
    last_verified_at TIMESTAMP      NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP       NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_linkable_type CHECK (linkable_type IN ('content', 'episode'))
);

CREATE INDEX idx_source_links_poly     ON source_links (linkable_type, linkable_id, status);
CREATE INDEX idx_source_links_source   ON source_links (source_id);

CREATE TABLE shadow_content_sources (
    id                  BIGSERIAL       PRIMARY KEY,
    source_id           BIGINT          NOT NULL REFERENCES sources(id) ON DELETE CASCADE,
    raw_filename        VARCHAR(2048)   NOT NULL,
    file_path           VARCHAR(2048)   NOT NULL,
    file_extension      VARCHAR(10)     NOT NULL,
    file_size           BIGINT          NULL,
    detected_encoding   VARCHAR(50)     NULL,
    subtitle_paths      JSONB           NULL,
    scan_batch_id       VARCHAR(100)    NOT NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_shadow_source    ON shadow_content_sources (source_id);
CREATE INDEX idx_shadow_batch     ON shadow_content_sources (scan_batch_id);

CREATE TABLE source_scan_logs (
    id              BIGSERIAL       PRIMARY KEY,
    source_id       BIGINT          NOT NULL REFERENCES sources(id) ON DELETE CASCADE,
    phase           VARCHAR(20)     NOT NULL,
    status          VARCHAR(50)     NOT NULL,
    items_found     INTEGER         NOT NULL DEFAULT 0,
    items_matched   INTEGER         NOT NULL DEFAULT 0,
    items_failed    INTEGER         NOT NULL DEFAULT 0,
    error_log       TEXT            NULL,
    started_at      TIMESTAMP       NOT NULL,
    completed_at    TIMESTAMP       NULL,
    CONSTRAINT chk_phase CHECK (phase IN ('collector', 'enricher')),
    CONSTRAINT chk_scan_status CHECK (status IN ('started', 'completed', 'failed'))
);

CREATE INDEX idx_scan_logs_source  ON source_scan_logs (source_id);
CREATE INDEX idx_scan_logs_started ON source_scan_logs (started_at);

-- ──────────────────────────────────────────────────────────────
-- Domain 5: User Activity
-- ──────────────────────────────────────────────────────────────

CREATE TABLE watchlists (
    id          BIGSERIAL   PRIMARY KEY,
    user_id     BIGINT      NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    content_id  BIGINT      NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
    created_at  TIMESTAMP   NOT NULL DEFAULT NOW(),
    UNIQUE (user_id, content_id)
);

CREATE TABLE favorites (
    id          BIGSERIAL   PRIMARY KEY,
    user_id     BIGINT      NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    content_id  BIGINT      NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
    created_at  TIMESTAMP   NOT NULL DEFAULT NOW(),
    UNIQUE (user_id, content_id)
);

CREATE TABLE watch_history (
    id              BIGSERIAL   PRIMARY KEY,
    user_id         BIGINT      NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    content_id      BIGINT      NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
    episode_id      BIGINT      NULL REFERENCES episodes(id) ON DELETE SET NULL,
    is_completed    BOOLEAN     NOT NULL DEFAULT FALSE,
    played_at       TIMESTAMP   NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_watch_history_user ON watch_history (user_id, played_at DESC);

-- Post-MVP
CREATE TABLE ratings (
    id          BIGSERIAL   PRIMARY KEY,
    user_id     BIGINT      NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    content_id  BIGINT      NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
    score       INTEGER     NOT NULL CHECK (score >= 1 AND score <= 10),
    created_at  TIMESTAMP   NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP   NOT NULL DEFAULT NOW(),
    UNIQUE (user_id, content_id)
);

-- Post-MVP
CREATE TABLE reviews (
    id          BIGSERIAL   PRIMARY KEY,
    user_id     BIGINT      NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    content_id  BIGINT      NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
    body        TEXT        NOT NULL,
    created_at  TIMESTAMP   NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP   NOT NULL DEFAULT NOW(),
    UNIQUE (user_id, content_id)
);

CREATE INDEX idx_reviews_content ON reviews (content_id);

-- ──────────────────────────────────────────────────────────────
-- Domain 6: Health Monitoring & System
-- ──────────────────────────────────────────────────────────────

CREATE TABLE source_health_reports (
    id                  BIGSERIAL       NOT NULL,
    source_id           BIGINT          NOT NULL,
    isp_name            VARCHAR(100)    NOT NULL,
    is_reachable        BOOLEAN         NOT NULL,
    response_time_ms    INTEGER         NULL,
    reported_at         TIMESTAMP       NOT NULL DEFAULT NOW(),
    PRIMARY KEY (id, reported_at)
) PARTITION BY RANGE (reported_at);

-- Create initial monthly partitions
CREATE TABLE source_health_reports_2026_01 PARTITION OF source_health_reports
    FOR VALUES FROM ('2026-01-01') TO ('2026-02-01');
CREATE TABLE source_health_reports_2026_02 PARTITION OF source_health_reports
    FOR VALUES FROM ('2026-02-01') TO ('2026-03-01');
CREATE TABLE source_health_reports_2026_03 PARTITION OF source_health_reports
    FOR VALUES FROM ('2026-03-01') TO ('2026-04-01');

CREATE INDEX idx_health_source_isp  ON source_health_reports (source_id, isp_name);
CREATE INDEX idx_health_reported    ON source_health_reports (reported_at);

CREATE TABLE settings (
    id          BIGSERIAL       PRIMARY KEY,
    "key"       VARCHAR(255)    NOT NULL UNIQUE,
    value       TEXT            NULL,
    "type"      VARCHAR(20)     NOT NULL DEFAULT 'string',
    created_at  TIMESTAMP       NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP       NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_setting_type CHECK ("type" IN ('string', 'integer', 'boolean', 'json'))
);

-- ──────────────────────────────────────────────────────────────
-- Triggers: Polymorphic FK integrity for source_links
-- (PostgreSQL can't enforce FKs on polymorphic columns)
-- ──────────────────────────────────────────────────────────────

CREATE OR REPLACE FUNCTION cascade_delete_source_links()
RETURNS TRIGGER AS $$
BEGIN
    DELETE FROM source_links
    WHERE linkable_type = TG_ARGV[0]
      AND linkable_id = OLD.id;
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_contents_delete_source_links
    BEFORE DELETE ON contents
    FOR EACH ROW
    EXECUTE FUNCTION cascade_delete_source_links('content');

CREATE TRIGGER trg_episodes_delete_source_links
    BEFORE DELETE ON episodes
    FOR EACH ROW
    EXECUTE FUNCTION cascade_delete_source_links('episode');

COMMIT;
