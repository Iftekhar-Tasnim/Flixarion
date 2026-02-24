-- ============================================================
-- Flixarion — MySQL 8.0+ Database Schema
-- 18 tables · 6 domains
-- ============================================================


-- ──────────────────────────────────────────────────────────────
-- Domain 1: Users & Auth
-- ──────────────────────────────────────────────────────────────

CREATE TABLE users (
    id              BIGINT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255)    NOT NULL,
    email           VARCHAR(255)    NOT NULL UNIQUE,
    email_verified_at DATETIME      NULL,
    password        VARCHAR(255)    NOT NULL,
    role            VARCHAR(50)     NOT NULL DEFAULT 'user',
    is_banned       TINYINT(1)      NOT NULL DEFAULT 0,
    remember_token  VARCHAR(100)    NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE personal_access_tokens (
    id              BIGINT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tokenable_type  VARCHAR(255)    NOT NULL,
    tokenable_id    BIGINT          NOT NULL,
    name            VARCHAR(255)    NOT NULL,
    token           VARCHAR(64)     NOT NULL UNIQUE,
    abilities       TEXT            NULL,
    expires_at      DATETIME        NULL,
    last_used_at    DATETIME        NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_pat_tokenable
    ON personal_access_tokens (tokenable_type, tokenable_id);

-- ──────────────────────────────────────────────────────────────
-- Domain 2: Content & Genres
-- ──────────────────────────────────────────────────────────────

CREATE TABLE contents (
    id                  BIGINT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tmdb_id             BIGINT          NOT NULL UNIQUE,
    imdb_id             VARCHAR(20)     NULL,
    type                VARCHAR(50)     NOT NULL,
    title               VARCHAR(500)    NOT NULL,
    original_title      VARCHAR(500)    NULL,
    `year`              INT             NULL,
    description         TEXT            NULL,
    poster_path         VARCHAR(500)    NULL,
    backdrop_path       VARCHAR(500)    NULL,
    `cast`              JSON            NULL,
    director            VARCHAR(255)    NULL,
    rating              DECIMAL(3,1)    NULL,
    vote_count          INT             NULL,
    runtime             INT             NULL,
    trailer_url         VARCHAR(500)    NULL,
    alternative_titles  JSON            NULL,
    `language`          VARCHAR(10)     NULL,
    `status`            VARCHAR(50)     NULL,
    enrichment_status   VARCHAR(50)     NOT NULL DEFAULT 'pending',
    confidence_score    DECIMAL(5,2)    NULL,
    is_published        TINYINT(1)      NOT NULL DEFAULT 0,
    is_featured         TINYINT(1)      NOT NULL DEFAULT 0,
    watch_count         INT             NOT NULL DEFAULT 0,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_contents_type              ON contents (type);
CREATE INDEX idx_contents_year              ON contents (`year`);
CREATE INDEX idx_contents_rating            ON contents (rating);
CREATE INDEX idx_contents_title             ON contents (title(255));
CREATE INDEX idx_contents_enrichment_status ON contents (enrichment_status);
CREATE INDEX idx_contents_imdb_id           ON contents (imdb_id);

-- MySQL 8.0+: multi-valued index on JSON arrays
-- If alternative_titles is an array of strings:
-- CREATE INDEX idx_contents_alt_titles ON contents ((CAST(alternative_titles AS CHAR(255) ARRAY)));
-- Otherwise, query via JSON_CONTAINS / JSON_EXTRACT (no GIN equivalent needed for small datasets).

CREATE TABLE genres (
    id          BIGINT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)    NOT NULL UNIQUE,
    slug        VARCHAR(100)    NOT NULL UNIQUE,
    tmdb_id     INT             NULL UNIQUE,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    content_id  BIGINT      NOT NULL,
    genre_id    BIGINT      NOT NULL,
    created_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (content_id, genre_id),
    CONSTRAINT fk_cg_content FOREIGN KEY (content_id) REFERENCES contents(id) ON DELETE CASCADE,
    CONSTRAINT fk_cg_genre   FOREIGN KEY (genre_id)   REFERENCES genres(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_content_genre_genre ON content_genre (genre_id);

-- ──────────────────────────────────────────────────────────────
-- Domain 3: Content Hierarchy (TV Series)
-- ──────────────────────────────────────────────────────────────

CREATE TABLE seasons (
    id              BIGINT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    content_id      BIGINT          NOT NULL,
    season_number   INT             NOT NULL,
    tmdb_season_id  BIGINT          NULL,
    title           VARCHAR(255)    NULL,
    poster_path     VARCHAR(500)    NULL,
    overview        TEXT            NULL,
    episode_count   INT             NULL,
    air_date        DATE            NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_season (content_id, season_number),
    CONSTRAINT fk_seasons_content FOREIGN KEY (content_id) REFERENCES contents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_seasons_content ON seasons (content_id);

CREATE TABLE episodes (
    id                  BIGINT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    season_id           BIGINT          NOT NULL,
    content_id          BIGINT          NOT NULL,
    episode_number      INT             NOT NULL,
    title               VARCHAR(255)    NULL,
    tmdb_episode_id     BIGINT          NULL,
    overview            TEXT            NULL,
    still_path          VARCHAR(500)    NULL,
    runtime             INT             NULL,
    air_date            DATE            NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_episode (season_id, episode_number),
    CONSTRAINT fk_episodes_season  FOREIGN KEY (season_id)  REFERENCES seasons(id)  ON DELETE CASCADE,
    CONSTRAINT fk_episodes_content FOREIGN KEY (content_id) REFERENCES contents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_episodes_content ON episodes (content_id);

-- ──────────────────────────────────────────────────────────────
-- Domain 4: Sources & Scanning
-- ──────────────────────────────────────────────────────────────

CREATE TABLE sources (
    id              BIGINT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255)    NOT NULL UNIQUE,
    base_url        VARCHAR(500)    NOT NULL,
    scraper_type    VARCHAR(100)    NOT NULL,
    config          JSON            NULL,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    health_score    DECIMAL(5,2)    NOT NULL DEFAULT 100.00,
    priority        INT             NOT NULL DEFAULT 0,
    last_scan_at    DATETIME        NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE source_links (
    id               BIGINT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    linkable_type    VARCHAR(50)     NOT NULL,
    linkable_id      BIGINT          NOT NULL,
    source_id        BIGINT          NOT NULL,
    file_path        VARCHAR(2048)   NOT NULL,
    quality          VARCHAR(50)     NULL,
    file_size        BIGINT          NULL,
    codec_info       VARCHAR(100)    NULL,
    part_number      INT             NULL,
    subtitle_paths   JSON            NULL,
    `status`         VARCHAR(50)     NOT NULL DEFAULT 'pending',
    last_verified_at DATETIME        NULL,
    created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sl_source FOREIGN KEY (source_id) REFERENCES sources(id) ON DELETE CASCADE,
    CONSTRAINT chk_linkable_type CHECK (linkable_type IN ('content', 'episode'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_source_links_poly     ON source_links (linkable_type, linkable_id, `status`);
CREATE INDEX idx_source_links_source   ON source_links (source_id);

CREATE TABLE shadow_content_sources (
    id                  BIGINT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    source_id           BIGINT          NOT NULL,
    raw_filename        VARCHAR(2048)   NOT NULL,
    file_path           VARCHAR(2048)   NOT NULL,
    file_extension      VARCHAR(10)     NOT NULL,
    file_size           BIGINT          NULL,
    detected_encoding   VARCHAR(50)     NULL,
    subtitle_paths      JSON            NULL,
    scan_batch_id       VARCHAR(100)    NOT NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_scs_source FOREIGN KEY (source_id) REFERENCES sources(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_shadow_source    ON shadow_content_sources (source_id);
CREATE INDEX idx_shadow_batch     ON shadow_content_sources (scan_batch_id);

CREATE TABLE source_scan_logs (
    id              BIGINT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    source_id       BIGINT          NOT NULL,
    phase           VARCHAR(20)     NOT NULL,
    `status`        VARCHAR(50)     NOT NULL,
    items_found     INT             NOT NULL DEFAULT 0,
    items_matched   INT             NOT NULL DEFAULT 0,
    items_failed    INT             NOT NULL DEFAULT 0,
    error_log       TEXT            NULL,
    started_at      DATETIME        NOT NULL,
    completed_at    DATETIME        NULL,
    CONSTRAINT fk_ssl_source FOREIGN KEY (source_id) REFERENCES sources(id) ON DELETE CASCADE,
    CONSTRAINT chk_phase       CHECK (phase IN ('collector', 'enricher')),
    CONSTRAINT chk_scan_status CHECK (`status` IN ('started', 'completed', 'failed'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_scan_logs_source  ON source_scan_logs (source_id);
CREATE INDEX idx_scan_logs_started ON source_scan_logs (started_at);

-- ──────────────────────────────────────────────────────────────
-- Domain 5: User Activity
-- ──────────────────────────────────────────────────────────────

CREATE TABLE watchlists (
    id          BIGINT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT      NOT NULL,
    content_id  BIGINT      NOT NULL,
    created_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_watchlist (user_id, content_id),
    CONSTRAINT fk_wl_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_wl_content FOREIGN KEY (content_id) REFERENCES contents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE favorites (
    id          BIGINT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT      NOT NULL,
    content_id  BIGINT      NOT NULL,
    created_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_favorite (user_id, content_id),
    CONSTRAINT fk_fav_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_fav_content FOREIGN KEY (content_id) REFERENCES contents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE watch_history (
    id              BIGINT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT      NOT NULL,
    content_id      BIGINT      NOT NULL,
    episode_id      BIGINT      NULL,
    is_completed    TINYINT(1)  NOT NULL DEFAULT 0,
    played_at       DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wh_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_wh_content FOREIGN KEY (content_id) REFERENCES contents(id) ON DELETE CASCADE,
    CONSTRAINT fk_wh_episode FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_watch_history_user ON watch_history (user_id, played_at DESC);

-- Post-MVP
CREATE TABLE ratings (
    id          BIGINT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT      NOT NULL,
    content_id  BIGINT      NOT NULL,
    score       INT         NOT NULL,
    created_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rating (user_id, content_id),
    CONSTRAINT fk_rat_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_rat_content FOREIGN KEY (content_id) REFERENCES contents(id) ON DELETE CASCADE,
    CONSTRAINT chk_score      CHECK (score >= 1 AND score <= 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Post-MVP
CREATE TABLE reviews (
    id          BIGINT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT      NOT NULL,
    content_id  BIGINT      NOT NULL,
    body        TEXT        NOT NULL,
    created_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review (user_id, content_id),
    CONSTRAINT fk_rev_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_rev_content FOREIGN KEY (content_id) REFERENCES contents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_reviews_content ON reviews (content_id);

-- ──────────────────────────────────────────────────────────────
-- Domain 6: Health Monitoring & System
-- ──────────────────────────────────────────────────────────────

CREATE TABLE source_health_reports (
    id                  BIGINT          NOT NULL AUTO_INCREMENT,
    source_id           BIGINT          NOT NULL,
    isp_name            VARCHAR(100)    NOT NULL,
    is_reachable        TINYINT(1)      NOT NULL,
    response_time_ms    INT             NULL,
    reported_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id, reported_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  PARTITION BY RANGE (YEAR(reported_at) * 100 + MONTH(reported_at)) (
    PARTITION p2026_01 VALUES LESS THAN (202602),
    PARTITION p2026_02 VALUES LESS THAN (202603),
    PARTITION p2026_03 VALUES LESS THAN (202604),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

CREATE INDEX idx_health_source_isp  ON source_health_reports (source_id, isp_name);
CREATE INDEX idx_health_reported    ON source_health_reports (reported_at);

CREATE TABLE settings (
    id          BIGINT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `key`       VARCHAR(255)    NOT NULL UNIQUE,
    `value`     TEXT            NULL,
    `type`      VARCHAR(20)     NOT NULL DEFAULT 'string',
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_setting_type CHECK (`type` IN ('string', 'integer', 'boolean', 'json'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- Triggers: Polymorphic FK integrity for source_links
-- (MySQL doesn't enforce FKs on polymorphic columns either)
-- Single-statement triggers (no DELIMITER needed)
-- ──────────────────────────────────────────────────────────────

CREATE TRIGGER trg_contents_delete_source_links
    BEFORE DELETE ON contents
    FOR EACH ROW
    DELETE FROM source_links
    WHERE linkable_type = 'content'
      AND linkable_id = OLD.id;

CREATE TRIGGER trg_episodes_delete_source_links
    BEFORE DELETE ON episodes
    FOR EACH ROW
    DELETE FROM source_links
    WHERE linkable_type = 'episode'
      AND linkable_id = OLD.id;
