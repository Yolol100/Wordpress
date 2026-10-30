import csv
import importlib.util
import pathlib
import tempfile
import unittest

MODULE_PATH = pathlib.Path(__file__).resolve().parents[1] / 'scripts' / 'preflight_media_metadata_csv.py'
spec = importlib.util.spec_from_file_location('preflight_media_metadata_csv', MODULE_PATH)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


class TestMetadataOnlyPreflight(unittest.TestCase):
    def test_safe_updates_and_protected_changes(self):
        with tempfile.TemporaryDirectory() as folder:
            root = pathlib.Path(folder)
            before = root / 'before.csv'
            after = root / 'after.csv'
            names = ['ID', 'Bestandsnaam', 'Bestandspad', 'Nieuwe bestandsnaam', 'Slug', 'Titel', 'Alt-tag', 'Bijschrift', 'Beschrijving']
            baseline = [
                ['42', 'lesauto.webp', '2026/01/lesauto.webp', '', 'lesauto', 'Lesauto Stad A', 'Rijschool Stad A', 'Rijschool Stad A', 'Oud'],
                ['43', 'lesauto-2.webp', '2026/01/lesauto-2.webp', '', 'lesauto-2', 'Prima', 'Goede alt', 'Goed', 'Goed'],
            ]
            with before.open('w', encoding='utf-8', newline='') as handle:
                w = csv.writer(handle); w.writerow(names); w.writerows(baseline)
            with after.open('w', encoding='utf-8', newline='') as handle:
                w = csv.writer(handle); w.writerow(['ID', 'Titel', 'Alt-tag', 'Bijschrift', 'Beschrijving']); w.writerow(['42','Lesauto tijdens rijles','Lesauto','Rijles','Lesauto bij een rijles'])
            good = module.check(before, after)
            self.assertEqual(good['status'], 'PASS')
            self.assertEqual(good['changed_media_ids'], 1)
            with after.open('w', encoding='utf-8', newline='') as handle:
                w = csv.writer(handle); w.writerow(names); w.writerow(['42','lesauto.webp','2026/01/lesauto.webp','andere-naam','lesauto','New','New','New','New'])
            self.assertEqual(module.check(before, after)['status'], 'FAIL')
            with after.open('w', encoding='utf-8', newline='') as handle:
                w = csv.writer(handle); w.writerow(['ID','Titel']);w.writerow(['99','Onbekend'])
            self.assertEqual(module.check(before, after)['status'], 'FAIL')

    def test_duplicate_ids_and_unchanged(self):
        with tempfile.TemporaryDirectory() as folder:
            a = pathlib.Path(folder) / 'a.csv'
            b = pathlib.Path(folder) / 'b.csv'
            a.write_text('ID,Titel\n42,OK\n', encoding='utf-8')
            b.write_text('ID,Titel\n42,OK\n42,Wrong\n', encoding='utf-8')
            self.assertEqual(module.check(a,b)['status'],'FAIL')
            b.write_text('ID,Titel\n42,OK\n', encoding='utf-8')
            result = module.check(a,b)
            self.assertEqual(result['status'],'PASS_WITH_WARNINGS')
            self.assertEqual(result['changed_media_ids'],0)


if __name__ == '__main__':
    unittest.main()
