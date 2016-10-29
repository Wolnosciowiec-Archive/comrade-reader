Comrade Reader
==============

  [![Build Status](https://travis-ci.org/Wolnosciowiec/comrade-reader.svg?branch=master)](https://travis-ci.org/Wolnosciowiec/comrade-reader)
  [![Code quality rating](https://scrutinizer-ci.com/g/Wolnosciowiec/comrade-reader/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Wolnosciowiec/comrade-reader/)
  [![Code Climate](https://codeclimate.com/github/Wolnosciowiec/comrade-reader/badges/gpa.svg)](https://codeclimate.com/github/Wolnosciowiec/comrade-reader)

  Makes requests to API and allows
  to decode response to objects
 
  Written for Wolnościowiec as a bridge
  between microservices and comrades who
  wants to share the anarchist events,
  articles and news.

  http://wolnosciowiec.net
  
## Instalation

```
composer require wolnosciowiec/comrade-reader
composer dump-autoload -o
```

## Example usage

Given we have an API method "/colors/by-name/{{ colorName }}" on external server that is returning:

```
{
    "success": true,
    "data": {
        "id": 1,
        "color": "Black & Red"
    }
}
```

```php
<?php
// Color.php

class Color
{
    protected $id;
    protected $colorName;
    
    // getter, setter...
}

// ColorRepository.php
class ColorRepository extends AbstractApiRepository
{
    /**
     * @param string $eventUrlName
     * @return \Entity\Events\Event
     */
    public function getColorByName($colorName)
    {
        return $this->reader->request('GET', '/colors/by-name/' . $this->escape($colorName), '', 3600)
            ->decode(Color::class);
    }
}

// ExampleController.php

class ExampleController extends AbstractController
{
    public function viewAction()
    {
        $color = $this->getRepository()->getColorByName('Red & Black');
        dump($color);
    }
}

```

The result of our dump() should be an outputted object of Color type to the screen with private properties filled up.
