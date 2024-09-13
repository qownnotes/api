{ pkgs ? import <nixpkgs> {} }:
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
}
