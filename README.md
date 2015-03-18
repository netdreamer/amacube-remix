amacube-remix
=============

Alexander Köb's Amacube project all shaken up. (https://github.com/akoeb/amacube)


State of Affairs
=============

March 18, 2015:
This project is based off of an earlier snapshot of Alexander Köb's Amacube project.  I have broken the project into 3 seperate plugins (quarantine, policy, and wb_list).

This repository is becoming more complete and I using it in production.

Works well with Roundcube 1.0.5 but currently experiencing a bug with Roundcube 1.1.0.  There is an issue with roundcube appending duplicate querystring vars to a URL, which overwrite the intended value.  Could be the way I've implemented the quarantine button, could be roundcube.  Was hoping someone might throw some cycles at the issue to see whats going on.


Future Thoughts
- Add a "Message Preview" modal box to the quarantine
- Add support to train SA-Bayes filter when items are purged from the quarantine
