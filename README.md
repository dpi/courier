Courier provides a framework to send messages to identities.

Copyright (C) 2016 Daniel Phin (@dpi)

[![Build Status](https://travis-ci.org/dpi/courier.svg?branch=8.x-1.x)](https://travis-ci.org/dpi/courier)

# License

## Courier

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

## Semantic UI

The MIT License
http://opensource.org/licenses/MIT

Portions of [Semantic UI](http://semantic-ui.com/) are included with Courier.
These files are licensed under The MIT License and are denoted with the heading
'Semantic UI', accompanied by a copyright message, at the top of each file.

# About

Courier was originally designed for RNG (https://drupal.org/project/rng).

Courier bundles an Email channel, and a plugin which links it to Drupal users.

# Installation

Courier is primarily an API module. You don't typically need to configure
the module as dependent modules will do that for you.

## Optional integration

__Token__

It is highly recommended to install the contrib Token module 
(https://drupal.org/project/token). It automatically adds an highly improved 
token selection interface to message edit forms.

# Terms

 *  __Channel__: a template entity type. The entity type implements
    \Drupal\courier\ChannelInterface
 *  __CourierContext__: Specifies tokens which are available for replacement.
 *  __TemplateCollection__: A collection of templates, referencing a maximum of
    one of each channel. Each TemplateCollection is owned by an entity or used
    as a global default.
 *  __Template__: an channel entity.
 *  __Message__: A rendered Template. A template which has had its tokens
    replaced, and is ready or has been sent to an identity.
 *  __Identity__: A recipient of a message. Used only when a template collection
    ready to be sent.

# Model

    CourierContext(s) ─► TemplateCollection(s) ─┬─► Template(s)
                                                └─► (optional) Owner entity

# Usage

Please see the project websites for instructions:

 *  https://drupal.org/project/courier
 *  https://github.com/dpi/courier
 
# Building

## Semantic UI

```sh
npm install semantic-ui --save
cd semantic
gulp build
```
