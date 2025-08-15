<?php
// v1.0
use App\Helpers\View;
use App\Helpers\Url;

$mode = $mode ?? 'create';
$isEdit = ($mode==='edit');
$action = $isEdit ? 'index.php?route=coindesigners/update' : 'index.php?route=coindesigners/store';
?>
<section class="space-y-4 max-w-4xl">
  <h1 class="text-lg font-semibold"><?= $isEdit ? 'Upravit autora' : 'Přidat autora' ?></h1>
  <?= View::flashHtml() ?>

  <form method="post" action="<?= $action ?>" class="space-y-4" autocomplete="off">
    <?= View::csrfField() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><?php endif; ?>

    <div class="grid gap-4 sm:grid-cols-2">
      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Jméno* (max 64)</span>
        <input type="text" name="firstName" required maxlength="64"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars($item['firstName'] ?? '') ?>">
        <?php if (!empty($errors['firstName'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['firstName']) ?></div><?php endif; ?>
      </label>

      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Příjmení* (max 64)</span>
        <input type="text" name="lastName" required maxlength="64"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars($item['lastName'] ?? '') ?>">
        <?php if (!empty($errors['lastName'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['lastName']) ?></div><?php endif; ?>
      </label>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Rok narození</span>
        <input type="text" name="birthYear" maxlength="5" inputmode="numeric" pattern="^-?\d{1,4}$"
               placeholder="např. 1901 nebo -200"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars($item['birthYear'] ?? '') ?>">
        <?php if (!empty($errors['birthYear'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['birthYear']) ?></div><?php endif; ?>
      </label>

      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Rok úmrtí</span>
        <input type="text" name="deathYear" maxlength="5" inputmode="numeric" pattern="^-?\d{1,4}$"
               placeholder="např. 1975"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars($item['deathYear'] ?? '') ?>">
        <?php if (!empty($errors['deathYear'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['deathYear']) ?></div><?php endif; ?>
      </label>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Národnost (max 100)</span>
        <input type="text" name="nationality" maxlength="100"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars($item['nationality'] ?? '') ?>">
        <?php if (!empty($errors['nationality'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['nationality']) ?></div><?php endif; ?>
      </label>

      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Popis (max 255)</span>
        <input type="text" name="description" maxlength="255"
               class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               value="<?= htmlspecialchars($item['description'] ?? '') ?>">
        <?php if (!empty($errors['description'])): ?><div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['description']) ?></div><?php endif; ?>
      </label>
    </div>

    <label class="block">
      <span class="mb-1 block text-sm text-slate-300">Poznámka</span>
      <textarea name="note" rows="4"
                class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"><?= htmlspecialchars($item['note'] ?? '') ?></textarea>
    </label>

    <div class="flex justify-end gap-2 pt-2">
      <a href="<?= Url::build('coindesigners/list') ?>" class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">Zpět</a>
      <button type="submit" class="rounded-md bg-sky-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400">
        <?= $isEdit ? 'Uložit změny' : 'Vytvořit' ?>
      </button>
    </div>
  </form>
</section>
