---
title: Fix inherited associated fields
author: Ruslan Belziuk
author_email: ruslan@dumka.pro
author_github: @ruslanbelziuk
---
# Core
* Make the `ManyToOneAssociationFieldResolver` class always generate joins for associated tables of a parent entity, when it has a respective association, and the child one doesn't.
