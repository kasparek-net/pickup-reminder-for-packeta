# Builds Czech and Slovak .po from the POT. Run: python3 languages/build.py
# then: wp i18n make-mo languages && wp i18n make-php languages
import re, os
D = os.path.dirname(os.path.abspath(__file__))
POT = os.path.join(D, 'pickup-reminder-for-packeta.pot')
N = ' '

T = {
 'Pickup Reminder for Packeta': ('Připomínka vyzvednutí pro Zásilkovnu', 'Pripomienka vyzdvihnutia pre Packetu'),
 'https://wp.kasparek.app/': ('https://wp.kasparek.app/', 'https://wp.kasparek.app/'),
 'Jakub Kašpárek': ('Jakub Kašpárek', 'Jakub Kašpárek'),
 'https://github.com/kasparek-net': ('https://github.com/kasparek-net', 'https://github.com/kasparek-net'),
 'E-mails customers before the last day their Packeta (Zásilkovna) parcel can be picked up, so fewer cash-on-delivery parcels come back. Uses the parcel status and storage date synced by the official Packeta plugin.':
  ('Pošle zákaznici e-mail před posledním dnem, kdy si může vyzvednout zásilku ze Zásilkovny, takže se vrací méně dobírek. Stav zásilky a úložní dobu bere z oficiálního pluginu Zásilkovny.',
   'Pošle zákazníčke e-mail pred posledným dňom, keď si môže vyzdvihnúť zásielku z Packety, takže sa vracia menej dobierok. Stav zásielky a úložnú dobu berie z oficiálneho pluginu Packeta.'),
 'Pickup reminder': ('Připomínka vyzvednutí', 'Pripomienka vyzdvihnutia'),
 'You are not allowed to do this.': ('K tomu nemáte oprávnění.', 'Na to nemáte oprávnenie.'),
 'Settings saved.': ('Nastavení uloženo.', 'Nastavenia uložené.'),
 'Sent %1$d of %2$d reminders. Check that the e-mail is enabled in WooCommerce → Settings → Emails.':
  ('Odesláno %1$d z %2$d připomínek. Zkontrolujte, že je e-mail zapnutý ve WooCommerce → Nastavení → E-maily.',
   'Odoslané %1$d z %2$d pripomienok. Skontrolujte, či je e-mail zapnutý vo WooCommerce → Nastavenia → E-maily.'),
 ('Check done, %d reminder sent.', 'Check done, %d reminders sent.'):
  (['Kontrola hotová, odeslána %d připomínka.', 'Kontrola hotová, odeslány %d připomínky.', 'Kontrola hotová, odesláno %d připomínek.'],
   ['Kontrola hotová, odoslaná %d pripomienka.', 'Kontrola hotová, odoslané %d pripomienky.', 'Kontrola hotová, odoslaných %d pripomienok.']),
 'Customers get an e-mail before the last day their Packeta parcel can be picked up, so fewer parcels are sent back. Parcel status and the storage date come from the official Packeta plugin.':
  ('Zákaznice dostane e-mail před posledním dnem, kdy si může zásilku ze Zásilkovny vyzvednout, takže se méně zásilek vrací zpět. Stav zásilky i úložní dobu bere plugin z oficiálního pluginu Zásilkovny.',
   'Zákazníčka dostane e-mail pred posledným dňom, keď si môže zásielku z Packety vyzdvihnúť, takže sa menej zásielok vracia späť. Stav zásielky aj úložnú dobu berie plugin z oficiálneho pluginu Packeta.'),
 'The Packeta plugin table was not found. Install and set up the official Packeta plugin including packet status synchronization.':
  ('Nenašla se tabulka pluginu Zásilkovny. Nainstalujte a nastavte oficiální plugin Zásilkovny včetně synchronizace stavů zásilek.',
   'Nenašla sa tabuľka pluginu Packeta. Nainštalujte a nastavte oficiálny plugin Packeta vrátane synchronizácie stavov zásielok.'),
 'The reminder e-mail is turned off in WooCommerce → Settings → Emails.':
  ('E-mail s připomínkou je vypnutý ve WooCommerce → Nastavení → E-maily.', 'E-mail s pripomienkou je vypnutý vo WooCommerce → Nastavenia → E-maily.'),
 'Live': ('Ostrý provoz', 'Ostrá prevádzka'),
 'Test mode': ('Testovací režim', 'Testovací režim'),
 'Reminders go to customers.': ('Připomínky chodí zákaznicím.', 'Pripomienky chodia zákazníčkam.'),
 'Reminders go only to %s, customers get nothing.': ('Připomínky chodí jen na %s, zákaznicím nic.', 'Pripomienky chodia len na %s, zákazníčkam nič.'),
 'parcels waiting at a pickup point': ('zásilek čeká na výdejním místě', 'zásielok čaká na výdajnom mieste'),
 'last check (runs every hour, e-mails 8:00–20:00)': ('poslední kontrola (běží každou hodinu, e-maily 8:00–20:00)', 'posledná kontrola (beží každú hodinu, e-maily 8:00–20:00)'),
 'Check now': ('Zkontrolovat teď', 'Skontrolovať teraz'),
 'Settings': ('Nastavení', 'Nastavenia'),
 'Mode': ('Režim', 'Režim'),
 'Test – send reminders only to this address:': ('Testovací – připomínky posílat jen na tuto adresu:', 'Testovací – pripomienky posielať len na túto adresu:'),
 'Live – send reminders to customers': ('Ostrý – připomínky posílat zákaznicím', 'Ostrý – pripomienky posielať zákazníčkam'),
 'When': ('Kdy', 'Kedy'),
 'on the last day of storage': ('v poslední den úložní doby', 'v posledný deň úložnej doby'),
 '1 day before the last day': ('1 den před posledním dnem', '1 deň pred posledným dňom'),
 '2 days before the last day': ('2 dny před posledním dnem', '2 dni pred posledným dňom'),
 '3 days before the last day': ('3 dny před posledním dnem', '3 dni pred posledným dňom'),
 'Which parcels': ('Které zásilky', 'Ktoré zásielky'),
 'Cash on delivery only (unpaid parcels cost you return postage)': ('Jen dobírky (nezaplacená vrácená zásilka vás stojí poštovné)', 'Len dobierky (nezaplatená vrátená zásielka vás stojí poštovné)'),
 'E-mail text': ('Text e-mailu', 'Text e-mailu'),
 'Edit subject, heading and text': ('Upravit předmět, nadpis a text', 'Upraviť predmet, nadpis a text'),
 'Save settings': ('Uložit nastavení', 'Uložiť nastavenia'),
 'Parcels waiting at a pickup point': ('Zásilky na výdejním místě', 'Zásielky na výdajnom mieste'),
 'No parcel is waiting for pickup right now.': ('Teď na vyzvednutí nečeká žádná zásilka.', 'Teraz na vyzdvihnutie nečaká žiadna zásielka.'),
 'Order': ('Objednávka', 'Objednávka'),
 'Stored until': ('Uloženo do', 'Uložené do'),
 'Cash on delivery': ('Dobírka', 'Dobierka'),
 'Pickup point': ('Výdejní místo', 'Výdajné miesto'),
 'Reminder': ('Připomínka', 'Pripomienka'),
 'sent': ('odeslána', 'odoslaná'),
 'test sent': ('odeslán test', 'odoslaný test'),
 'not sent (paid)': ('neposílá se (zaplaceno)', 'neposiela sa (zaplatené)'),
 'not sent (storage date passed)': ('neposílá se (termín už minul)', 'neposiela sa (termín už uplynul)'),
 'not sent (order cancelled)': ('neposílá se (objednávka zrušena)', 'neposiela sa (objednávka zrušená)'),
 'today': ('dnes', 'dnes'),
 'planned': ('naplánováno', 'naplánované'),
 'yes': ('ano', 'áno'),
 'no': ('ne', 'nie'),
 'Pickup reminder (Packeta)': ('Připomínka vyzvednutí (Zásilkovna)', 'Pripomienka vyzdvihnutia (Packeta)'),
 'Sent to the customer before the last day their Packeta parcel can be picked up at the pickup point. When and to whom is set in WooCommerce → Pickup reminder.':
  ('Posílá se zákaznici před posledním dnem, kdy si může zásilku ze Zásilkovny vyzvednout na výdejním místě. Kdy a komu se nastavuje ve WooCommerce → Připomínka vyzvednutí.',
   'Posiela sa zákazníčke pred posledným dňom, keď si môže zásielku z Packety vyzdvihnúť na výdajnom mieste. Kedy a komu sa nastavuje vo WooCommerce → Pripomienka vyzdvihnutia.'),
 'Your parcel is waiting – pick it up by {stored_until}': ('Zásilka na vás čeká – vyzvedněte si ji do {stored_until}', 'Zásielka na vás čaká – vyzdvihnite si ju do {stored_until}'),
 'Your parcel is waiting for you': ('Zásilka na vás čeká', 'Zásielka na vás čaká'),
 'Hello, your parcel for order {order_number} is waiting for you at the pickup point. You can pick it up until {stored_until}; after that it will be sent back to us.':
  ('Dobrý den, zásilka s vaší objednávkou {order_number} na vás čeká na výdejním místě. Vyzvednout si ji můžete do {stored_until}, potom se vrací zpět k nám.',
   'Dobrý deň, zásielka s vašou objednávkou {order_number} na vás čaká na výdajnom mieste. Vyzdvihnúť si ju môžete do {stored_until}, potom sa vracia späť k nám.'),
 'Thank you for picking it up in time.': ('Děkujeme, že si ji vyzvednete včas.', 'Ďakujeme, že si ju vyzdvihnete včas.'),
 '[Test for %1$s] %2$s': ('[Test pro %1$s] %2$s', '[Test pre %1$s] %2$s'),
 'Text': ('Text', 'Text'),
 'Text above the parcel details. Available placeholders: %s': ('Text nad údaji o zásilce. Můžete použít: %s', 'Text nad údajmi o zásielke. Môžete použiť: %s'),
 'Pickup reminder sent to the customer (parcel stored until %s).': ('Zákaznici odeslána připomínka vyzvednutí (zásilka uložena do %s).', 'Zákazníčke odoslaná pripomienka vyzdvihnutia (zásielka uložená do %s).'),
 'Pick up by': ('Vyzvedněte do', 'Vyzdvihnite do'),
 'To pay on pickup': ('Zaplatíte při převzetí', 'Zaplatíte pri prevzatí'),
 'Parcel number': ('Číslo zásilky', 'Číslo zásielky'),
 'Opening hours and map': ('Otevírací doba a mapa', 'Otváracie hodiny a mapa'),
}

def nbsp(s):
    s = re.sub(r'(?<![\w])([vVkKsSzZoOuUaAiI]) ', r'\1' + N, s)
    s = re.sub(r'(\d) (dny?|dni|den|deň)', r'\1' + N + r'\2', s)
    return s

def esc(s): return s.replace('\\', '\\\\').replace('"', '\\"')

def unq(block):
    return ''.join(re.findall(r'"((?:[^"\\]|\\.)*)"', block)).replace('\\"', '"').replace('\\\\', '\\')

pot = open(POT, encoding='utf-8').read()
entries = pot.split('\n\n')
for idx, (loc, plural) in enumerate([('cs_CZ', 'nplurals=3; plural=(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2;'), ('sk_SK', 'nplurals=3; plural=(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2;')]):
    out, missing = [], 0
    for e in entries:
        if not e.strip(): continue
        m = re.search(r'^msgid ((?:".*"\n?)+)', e, re.M)
        if not m: continue
        mid = unq(m.group(1))
        head = e[:e.index('msgid')]
        if mid == '':
            out.append(head + 'msgid ""\nmsgstr ""\n"Content-Type: text/plain; charset=UTF-8\\n"\n"Language: %s\\n"\n"Plural-Forms: %s\\n"\n"X-Domain: pickup-reminder-for-packeta\\n"' % (loc, plural))
            continue
        pm = re.search(r'^msgid_plural ((?:".*"\n?)+)', e, re.M)
        if pm:
            key = (mid, unq(pm.group(1)))
            tr = T.get(key)
            if not tr: missing += 1; continue
            forms = tr[idx]
            if loc == 'cs_CZ': forms = [nbsp(f) for f in forms]
            out.append(head + 'msgid "%s"\nmsgid_plural "%s"\n' % (esc(key[0]), esc(key[1])) + '\n'.join('msgstr[%d] "%s"' % (i, esc(f)) for i, f in enumerate(forms)))
        else:
            tr = T.get(mid)
            if not tr: missing += 1; print('MISSING', mid); continue
            s = tr[idx]
            if loc == 'cs_CZ': s = nbsp(s)
            out.append(head + 'msgid "%s"\nmsgstr "%s"' % (esc(mid), esc(s)))
    open(os.path.join(D, 'pickup-reminder-for-packeta-%s.po' % loc), 'w', encoding='utf-8').write('\n\n'.join(out) + '\n')
    print(loc, 'entries', len(out) - 1, 'missing', missing)
