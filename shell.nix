{ pkgs ? import <nixpkgs> {} }:
  pkgs.mkShell {
    nativeBuildInputs = [
      pkgs.symfony-cli
      pkgs.php81
      pkgs.php81Packages.composer
    ];
}
