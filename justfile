term:
	zellij --layout term.kdl attach qownnotes-api -cf

term-kill:
	zellij delete-session qownnotes-api -f

clear-cache:
  rm -rf var/cache/*
