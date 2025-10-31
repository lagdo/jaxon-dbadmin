<?php

namespace Lagdo\DbAdmin;

use Lagdo\DbAdmin\Driver\Utils\TranslatorInterface;

use function func_get_args;
use function array_shift;
use function str_replace;
use function vsprintf;
use function strtr;
use function number_format;
use function preg_split;
use function max;
use function microtime;

/**
 * Translator and language switcher
 *
 * Not used in a single language version
 */
class Translator implements TranslatorInterface
{
    /**
     * Available languages
     *
     * @var array
     */
    /*protected $languages = [
        'en' => 'English', // Jakub Vrána - https://www.vrana.cz
        'ar' => 'العربية', // Y.M Amine - Algeria - nbr7@live.fr
        'bg' => 'Български', // Deyan Delchev
        'bn' => 'বাংলা', // Dipak Kumar - dipak.ndc@gmail.com
        'bs' => 'Bosanski', // Emir Kurtovic
        'ca' => 'Català', // Joan Llosas
        'cs' => 'Čeština', // Jakub Vrána - https://www.vrana.cz
        'da' => 'Dansk', // Jarne W. Beutnagel - jarne@beutnagel.dk
        'de' => 'Deutsch', // Klemens Häckel - http://clickdimension.wordpress.com
        'el' => 'Ελληνικά', // Dimitrios T. Tanis - jtanis@tanisfood.gr
        'es' => 'Español', // Klemens Häckel - http://clickdimension.wordpress.com
        'et' => 'Eesti', // Priit Kallas
        'fa' => 'فارسی', // mojtaba barghbani - Iran - mbarghbani@gmail.com, Nima Amini - http://nimlog.com
        'fi' => 'Suomi', // Finnish - Kari Eveli - http://www.lexitec.fi/
        'fr' => 'Français', // Francis Gagné, Aurélien Royer
        'gl' => 'Galego', // Eduardo Penabad Ramos
        'he' => 'עברית', // Binyamin Yawitz - https://stuff-group.com/
        'hu' => 'Magyar', // Borsos Szilárd (Borsosfi) - http://www.borsosfi.hu, info@borsosfi.hu
        'id' => 'Bahasa Indonesia', // Ivan Lanin - http://ivan.lanin.org
        'it' => 'Italiano', // Alessandro Fiorotto, Paolo Asperti
        'ja' => '日本語', // Hitoshi Ozawa - http://sourceforge.jp/projects/oss-ja-jpn/releases/
        'ka' => 'ქართული', // Saba Khmaladze skhmaladze@uglt.org
        'ko' => '한국어', // dalli - skcha67@gmail.com
        'lt' => 'Lietuvių', // Paulius Leščinskas - http://www.lescinskas.lt
        'ms' => 'Bahasa Melayu', // Pisyek
        'nl' => 'Nederlands', // Maarten Balliauw - http://blog.maartenballiauw.be
        'no' => 'Norsk', // Iver Odin Kvello, mupublishing.com
        'pl' => 'Polski', // Radosław Kowalewski - http://srsbiz.pl/
        'pt' => 'Português', // André Dias
        'pt-br' => 'Português (Brazil)', // Gian Live - gian@live.com, Davi Alexandre davi@davialexandre.com.br, RobertoPC - http://www.robertopc.com.br
        'ro' => 'Limba Română', // .nick .messing - dot.nick.dot.messing@gmail.com
        'ru' => 'Русский', // Maksim Izmaylov; Andre Polykanine - https://github.com/Oire/
        'sk' => 'Slovenčina', // Ivan Suchy - http://www.ivansuchy.com, Juraj Krivda - http://www.jstudio.cz
        'sl' => 'Slovenski', // Matej Ferlan - www.itdinamik.com, matej.ferlan@itdinamik.com
        'sr' => 'Српски', // Nikola Radovanović - cobisimo@gmail.com
        'sv' => 'Svenska', // rasmusolle - https://github.com/rasmusolle
        'ta' => 'த‌மிழ்', // G. Sampath Kumar, Chennai, India, sampathkumar11@gmail.com
        'th' => 'ภาษาไทย', // Panya Saraphi, elect.tu@gmail.com - http://www.opencart2u.com/
        'tr' => 'Türkçe', // Bilgehan Korkmaz - turktron.com
        'uk' => 'Українська', // Valerii Kryzhov
        'vi' => 'Tiếng Việt', // Giang Manh @ manhgd google mail
        'zh' => '简体中文', // Mr. Lodar, vea - urn2.net - vea.urn2@gmail.com
        'zh-tw' => '繁體中文', // http://tzangms.com
    ];*/

    /**
     * Current language
     *
     * @var string
     */
    protected $language;

    /**
     * Available translations
     *
     * @var array
     */
    protected $translations;

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->setLanguage('en');
    }

    /**
     * Set the current language
     *
     * @param string $language
     *
     * @return void
     */
    public function setLanguage(string $language)
    {
        $this->language = $language;
        $this->translations = require __DIR__ . "/../translations/$language.inc.php";
    }

    /**
     * Get the current language
     *
     * @return string
     */
    /*public function getLanguage()
    {
        return $this->language;
    }*/

    /**
     * Get a translated string
     *
     * @param string $string
     * @param mixed $number
     *
     * @return string
     */
    public function lang(string $string, $number = null): string
    {
        /*if (array_key_exists($string, $this->translations)) {
            $string = $this->translations[$string];
        }
        // Todo: use match
        if (is_array($string)) {
            $pos = ($number == 1 ? 0
                // different forms for 1, 2-4, other
                : ($this->language == 'cs' || $this->language == 'sk' ? ($number && $number < 5 ? 1 : 2)
                // different forms for 0-1, other
                : ($this->language == 'fr' ? (!$number ? 0 : 1)
                // different forms for 1, 2-4 except 12-14, other
                : ($this->language == 'pl' ? ($number % 10 > 1 && $number % 10 < 5 && $number / 10 % 10 != 1 ? 1 : 2)
                // different forms for 1, 2, 3-4, other
                : ($this->language == 'sl' ? ($number % 100 == 1 ? 0 : ($number % 100 == 2 ? 1 :
                    ($number % 100 == 3 || $number % 100 == 4 ? 2 : 3)))
                // different forms for 1, 12-19, other
                : ($this->language == 'lt' ? ($number % 10 == 1 && $number % 100 != 11 ? 0 :
                    ($number % 10 > 1 && $number / 10 % 10 != 1 ? 1 : 2))
                // different forms for 1 except 11, 2-4 except 12-14, other
                : ($this->language == 'bs' || $this->language == 'ru' || $this->language == 'sr' || $this->language == 'uk' ?
                    ($number % 10 == 1 && $number % 100 != 11 ? 0
                    : ($number % 10 > 1 && $number % 10 < 5 && $number / 10 % 10 != 1 ? 1 : 2))
                : 1 // different forms for 1, other
            ))))))); // http://www.gnu.org/software/gettext/manual/html_node/Plural-forms.html
            $string = $string[$pos];
        }*/
        $args = func_get_args();
        array_shift($args);
        $format = str_replace("%d", "%s", $string);
        if ($format !== $string) {
            $args[0] = $this->formatNumber($number);
        }
        return vsprintf($format, $args);
    }

    /**
     * Format a decimal number
     *
     * @param int $number
     *
     * @return string
     */
    public function formatNumber(int $number): string
    {
        return strtr(number_format($number, 0, ".", $this->lang(',')),
            preg_split('~~u', $this->lang('0123456789'), -1, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * Format elapsed time
     *
     * @param float $time Output of microtime(true)
     *
     * @return string
     */
    public function formatTime(float $time): string
    {
        return $this->lang('%.3f s', max(0, microtime(true) - $time));
    }
}
