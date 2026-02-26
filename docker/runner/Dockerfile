FROM ubuntu:24.04

ARG RUNNER_VERSION="2.322.0"
ARG DEBIAN_FRONTEND=noninteractive

# ── System packages ──────────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y \
    curl \
    wget \
    git \
    jq \
    unzip \
    software-properties-common \
    ca-certificates \
    gnupg \
    sqlite3 \
    && rm -rf /var/lib/apt/lists/*

# ── PHP 8.4 ──────────────────────────────────────────────────────────────────
RUN add-apt-repository ppa:ondrej/php -y && \
    apt-get update && \
    apt-get install -y \
        php8.4-cli \
        php8.4-common \
        php8.4-curl \
        php8.4-mbstring \
        php8.4-xml \
        php8.4-zip \
        php8.4-sqlite3 \
        php8.4-bcmath \
        php8.4-xdebug \
    && rm -rf /var/lib/apt/lists/*

# ── Composer ─────────────────────────────────────────────────────────────────
RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

# ── Node 22 ──────────────────────────────────────────────────────────────────
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && \
    apt-get install -y nodejs && \
    rm -rf /var/lib/apt/lists/*

# ── Playwright / Chromium system deps ────────────────────────────────────────
RUN apt-get update && apt-get install -y \
    libnss3 \
    libnspr4 \
    libdbus-1-3 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libcups2 \
    libdrm2 \
    libxkbcommon0 \
    libxcomposite1 \
    libxdamage1 \
    libxfixes3 \
    libxrandr2 \
    libgbm1 \
    libasound2t64 \
    libx11-6 \
    libx11-xcb1 \
    libxcb1 \
    libxext6 \
    && rm -rf /var/lib/apt/lists/*

# ── GitHub Actions runner ─────────────────────────────────────────────────────
RUN useradd -m -s /bin/bash runner

RUN ARCH=$(uname -m | sed 's/x86_64/x64/;s/aarch64/arm64/') && \
    mkdir -p /home/runner/actions-runner && \
    cd /home/runner/actions-runner && \
    curl -fsSL -o runner.tar.gz \
        "https://github.com/actions/runner/releases/download/v${RUNNER_VERSION}/actions-runner-linux-${ARCH}-${RUNNER_VERSION}.tar.gz" && \
    tar xzf runner.tar.gz && \
    rm runner.tar.gz && \
    chown -R runner:runner /home/runner/actions-runner

RUN /home/runner/actions-runner/bin/installdeps.sh

# ── Pre-install Playwright Chromium as runner user ───────────────────────────
USER runner
RUN cd /home/runner && npx playwright install chromium 2>/dev/null || true

USER root
COPY start.sh /start.sh
RUN chmod +x /start.sh

USER runner
WORKDIR /home/runner/actions-runner

ENTRYPOINT ["/start.sh"]
