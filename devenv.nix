{
  pkgs,
  config,
  ...
}:

{
  # https://devenv.sh/supported-languages/php/
  languages.php = {
    enable = true;
    version = "8.3";
    ini = ''
      memory_limit = 256M
    '';
    fpm.pools.web = {
      settings = {
        "pm" = "dynamic";
        "pm.max_children" = 5;
        "pm.start_servers" = 2;
        "pm.min_spare_servers" = 1;
        "pm.max_spare_servers" = 5;
      };
      phpEnv = {
        MATOMO_URL = "https://p.qownnotes.org";
      };
    };
  };

  services.caddy.enable = true;
  services.caddy.virtualHosts."http://localhost:8000" = {
    extraConfig = ''
      root * public
      php_fastcgi unix/${config.languages.php.fpm.pools.web.socket}
      file_server
    '';
  };

  # https://devenv.sh/packages/
  packages = with pkgs; [
    git
    symfony-cli
    just # task runner
    zellij # smart terminal workspace
    lazygit # git terminal
    fzf # fuzzy finder

    # Packages for treefmt
    nodePackages.prettier
    nixfmt-rfc-style
    shfmt
    statix
    taplo
    php83Packages.php-cs-fixer
  ];

  # https://devenv.sh/git-hooks/
  # git-hooks.hooks.shellcheck.enable = true;
  git-hooks.hooks.treefmt.enable = true;

  # https://devenv.sh/integrations/dotenv/
  dotenv.enable = true;
  dotenv.filename = [
    ".env"
    ".env.local"
  ];

  # See full reference at https://devenv.sh/reference/options/
}
