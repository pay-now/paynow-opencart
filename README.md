[**English version**][ext0]

# Wtyczka Paynow dla OpenCart Payment

Wtyczka Paynow dodaje szybkie płatności i płatności BLIK do sklepu OpenCart.

Wtyczka wspiera OpenCart w wersji 3.0 lub wyższej.

## Spis treści

- [Instalacja](#instalacja)
- [Konfiguracja](#konfiguracja)
- [FAQ](#FAQ)
- [Sandbox](#sandbox)
- [Wsparcie](#wsparcie)
- [Licencja](#licencja)

## Instalacja

1. Pobierz plik paynow.ocmod.zip [repozytorium Github][ext1] i zapisz na dysku swojego komputera
2. Przjedź do panelu administracyjnego OpenCart
3. Przejdź do zakładki `Extensions > Installer`

![Instalacja krok 3][ext3]

4. W sekcji `Upload your extension` kliknij przycisk `Upload` i wskaż archiwum zawierające wtyczkę (pobrane w kroku 1.)

![Instalacja krok 4][ext4]

## Konfiguracja

1. Przejdź do zakładki `Extensions > Extensions`
2. Z listy filtrów wybierz `Payments`

![Konfiguracja krok 2][ext5]

3. Znajdź na liście `Paynow` a następnie wybierz opcję `Edit`

![Konfiguracja krok 3][ext6]

4. Produkcyjne klucze dostępu znajdziesz w zakładce `Paynow >Ustawienia > Sklepy i punkty płatności > Dane uwierzytelniające` w bankowości internetowej mBanku.

   Klucze dla środowiska testowego znajdziesz w zakładce `Ustawienia > Sklepy i punkty płatności > Dane uwierzytelniające` w [panelu środowiska testowego][ext11].

![Konfiguracja krok 4][ext8]

5. W zależności od środowiska, z którym chesz się połaczyć wpisz:

- dla środowiska produkcyjnego
  - `API Key (Production)`
  - `Signature Key (Production)`
- dla środowiska testowego
  - `API Key (Sandbox)`
  - `Signature Key (Sandbox)`

![Konfiguracja krok 5][ext9]

## FAQ

**Jak skonfigurować adres powiadomień?**

Adres powrotu ustawi się automatycznie po opłaceniu pierwszego zamówienia. W przypadku problemów w panelu sprzedawcy Paynow przejdź do zakładki `Ustawienia > Sklepy i punkty płatności`, w polu `Adres powiadomień` ustaw adres:
`https://twoja-domena.pl/index.php?route=extension/payment/paynow/notifications`.

**Jak skonfigurować adres powrotu?**

Adres powrotu ustawi się automatycznie po opłaceniu pierwszego zamówienia. W przypadku problemów w panelu sprzedawcy Paynow przejdź do zakładki `Ustawienia > Sklepy i punkty płatności > Punkt płatności`, w polu `Adres powrotu` ustaw adres:
`https://twoja-domena.pl/index.php?route=checkout/success`.

![FAQ][ext12]

## Sandbox

W celu przetestowania działania bramki Paynow zapraszamy do skorzystania z naszego środowiska testowego. W tym celu zarejestruj się na stronie: [panel.sandbox.paynow.pl][ext2].

## Wsparcie

Jeśli masz jakiekolwiek pytania lub problemy, skontaktuj się z naszym wsparciem technicznym: support@paynow.pl.

## Więcej informacji

Jeśli chciałbyś dowiedzieć się więcej o bramce płatności Paynow odwiedź naszą stronę: https://www.paynow.pl/

## Licencja

Licencja MIT. Szczegółowe informacje znajdziesz w pliku LICENSE.

[ext0]: README.EN.md
[ext1]: https://github.com/pay-now/paynow-opencart/releases/latest
[ext2]: https://panel.sandbox.paynow.pl/auth/register
[ext3]: instruction/step1.png
[ext4]: instruction/step2.png
[ext5]: instruction/step3.png
[ext6]: instruction/step4.png
[ext7]: instruction/step5.png
[ext8]: instruction/step6.png
[ext9]: instruction/step7.png
[ext10]: instruction/step8.png
[ext11]: https://panel.sandbox.paynow.pl/merchant/settings/shops-and-pos
[ext12]: instruction/faq.png
