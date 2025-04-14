# Use `just <recipe>` to run a recipe
# https://just.systems/man/en/

# By default, run the `--list` command
default:
    @just --list

# Variables

transferDir := `if [ -d "$HOME/NextcloudPrivate/Transfer" ]; then echo "$HOME/NextcloudPrivate/Transfer"; else echo "$HOME/Nextcloud/Transfer"; fi`
sessionName := "qownnotes-api"

## Aliases

alias fmt := format

# Open a terminal with the qownnotes-api session
[group('dev')]
term: term-kill
    zellij --layout term.kdl attach {{ sessionName }} -c

# Kill the qownnotes-api session
[group('dev')]
term-kill:
    zellij delete-session {{ sessionName }} -f

# Clear the cache
[group('dev')]
clear-cache:
    rm -rf var/cache/*

# Apply the patch to the qownnotes-api repository
[group('patch')]
git-apply-patch:
    git apply {{ transferDir }}/{{ sessionName }}.patch

# Create a patch from the staged changes in the qownnotes-api repository
[group('patch')]
@git-create-patch:
    echo "transferDir: {{ transferDir }}"
    git diff --no-ext-diff --staged --binary > {{ transferDir }}/{{ sessionName }}.patch
    ls -l1t {{ transferDir }}/ | head -2

# Format all files
[group('linter')]
format args='':
    nix-shell -p treefmt just nodePackages.prettier nixfmt-rfc-style shfmt statix taplo php83Packages.php-cs-fixer --run "treefmt {{ args }}"
