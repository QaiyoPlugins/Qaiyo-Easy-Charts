# Qaiyo Easy Charts

Egyszerű, szép, animált chartok WordPresshez **Elementor** és **Bricks Builder** alá.

## Chartok

### Range Bars (sáv/tartomány diagram)

Vízszintes sávok egy teli alapértékkel és egy opcionális kiemelt tartománnyal (pl. „10–15 munkanap”), közös tengellyel.

- Soronként: címke, érték (sötét sáv vége), tartomány vége (világos sáv vége, opcionális), egyedi értékszöveg (pl. „akár 5”), egyedi színek
- Skála: minimum, maximum (üresen hagyva automatikus), lépésköz, mértékegység (utolsó / minden / egyik osztásnál sem)
- Reszponzív: a chart **saját szélessége** alapján (container query) 560px alatt a címke és az érték a sáv fölé kerül, 480px alatt a köztes tengelyfeliratok ritkulnak. Kényszeríthető „mindig egy sorban” vagy „mindig egymás alatt” elrendezés is.
- Görgetésre induló animáció (a sávok egymás után nőnek, a tartomány utána), `prefers-reduced-motion` tisztelete; JS nélkül statikusan jelenik meg
- Hoverkor a többi sor elhalványul (kikapcsolható)
- Minden szín, méret, térköz és tipográfia állítható a builderből (CSS változókon keresztül)

## Felépítés

```
qaiyo-easy-charts.php                         plugin fejléc, bootstrap
includes/class-plugin.php                     assetek, Elementor + Bricks regisztráció
includes/class-helpers.php                    közös segédfüggvények
includes/charts/class-range-bars.php          a chart HTML-je (közös mindkét buildernek)
includes/integrations/elementor/...           Elementor widget (vezérlők → args)
includes/integrations/bricks/...              Bricks elem (vezérlők → args)
assets/css/easy-charts.css                    stílusok, CSS változók
assets/js/easy-charts.js                      görgetéses animáció
```

Új chart típus: új osztály az `includes/charts/` alatt, majd egy-egy vékony Elementor widget és Bricks elem, ami a beállításokat a chart argumentumaivá alakítja.

## Követelmények

WordPress 6.0+, PHP 7.4+, Elementor 3.5+ és/vagy Bricks 1.5+.
