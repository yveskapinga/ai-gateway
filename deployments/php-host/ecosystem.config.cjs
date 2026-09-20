/**
 * AI gateway generate/embed host — loopback only.
 */
module.exports = {
  apps: [
    {
      name: "ai-gateway",
      script: "/usr/bin/php8.3",
      args: "-c /var/lib/.local-state/yves/workspace/ai-gateway/deployments/php-host/php.ini -S 127.0.0.1:18190 index.php",
      cwd: "/var/lib/.local-state/yves/workspace/ai-gateway/deployments/php-host",
      interpreter: "none",
      env: {
        AIGW_API_KEY: "dev_only_change_me",
        AIGW_ENV_FILE: "/var/lib/.local-state/yves/workspace/ssk-book/api/.env.prod.local",
      },
      log_file: "/var/www/test/_runtime/logs/ai-gateway.log",
      merge_logs: true,
      autorestart: true,
    },
  ],
};
