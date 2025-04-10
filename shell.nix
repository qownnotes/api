{
  pkgs ? import <nixpkgs> { },
}:
pkgs.mkShell {
  nativeBuildInputs = with pkgs; [
    symfony-cli
    php83
    php83Packages.composer
    just # task runner
    zellij # smart terminal workspace
    lazygit # git terminal
    fzf # fuzzy finder
  ];

  shellHook = ''
    # Determine the repository root
    REPO_ROOT=$(git rev-parse --show-toplevel 2>/dev/null)

    # Check if we are in the repository root
    if [ "$REPO_ROOT" = "$(pwd)" ]; then
      # Symlink the pre-commit hook into the .git/hooks directory
      ln -sf ../../scripts/pre-commit.sh .git/hooks/pre-commit
    fi
  '';
}
