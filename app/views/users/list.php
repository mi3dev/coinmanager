<?php
// v1.2
use App\Helpers\Url;
use App\Helpers\Sort;
use App\Helpers\View;

// helper pro klikací hlavičky
function sortLink($label, $column, $currentSort, $currentDir) {
    $next  = Sort::nextDir($column, $currentSort, $currentDir);
    $url   = Url::build('users/list', ['sort'=>$column, 'dir'=>$next, 'page'=>1]); // při změně sortu na stranu 1
    $caret = ($currentSort === $column) ? ($currentDir === 'ASC' ? ' ▲' : ' ▼') : '';
    return '<a href="'.$url.'" class="hover:underline">'.$label.$caret.'</a>';
}
$routeForPager = $_GET['route'] ?? 'users/list';
?>
<section class="space-y-4">
  <div class="flex items-center justify-between">
    <h1 class="text-lg font-semibold">Uživatelé</h1>
    <a href="<?= Url::build('users/create') ?>" class="rounded-md bg-sky-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400">
      + Přidat uživatele
    </a>
  </div>

  <form method="get" class="flex gap-2">
    <input type="hidden" name="route" value="users/list">
    <input class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
           type="text" name="q" placeholder="Hledat podle username nebo e‑mailu…"
           value="<?= htmlspecialchars($q ?? '') ?>">
    <button class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">Hledat</button>
  </form>

  <?= View::flashHtml() ?>

  <div class="overflow-x-auto rounded-xl border border-slate-800">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-900 text-slate-400">
        <tr>
          <th class="px-3 py-2 text-left"><?= sortLink('ID','id',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Username','username',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('E‑mail','email',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Role','role',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Stav','active',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Vytvořen','created_at',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-right">Akce</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-800">
        <?php foreach ($rows as $u): ?>
          <tr class="hover:bg-slate-800/40">
            <td class="px-3 py-2"><?= (int)$u['id'] ?></td>
            <td class="px-3 py-2"><?= htmlspecialchars($u['username']) ?></td>
            <td class="px-3 py-2"><?= htmlspecialchars($u['email']) ?></td>
            <td class="px-3 py-2"><?= htmlspecialchars($u['role']) ?></td>
            <td class="px-3 py-2">
              <?php if ($u['active']): ?>
                <span class="rounded bg-emerald-600/20 px-2 py-0.5 text-emerald-300">aktivní</span>
              <?php else: ?>
                <span class="rounded bg-red-600/20 px-2 py-0.5 text-red-300">neaktivní</span>
              <?php endif; ?>
            </td>
            <td class="px-3 py-2"><?= htmlspecialchars($u['created_at'] ?? '') ?></td>
            <td class="px-3 py-2 text-right">
              <a class="rounded-md border border-slate-700 px-2 py-1 text-xs hover:bg-slate-800"
                 href="<?= Url::build('users/edit', ['id'=>$u['id']]) ?>">Upravit</a>
              <form class="inline-block" method="post" action="<?= Url::build('users/toggle') ?>"
                    onsubmit="return confirm('Opravdu změnit stav?');">
                <?= View::csrfField() ?>
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <?php if ($u['active']): ?>
                  <button class="rounded-md border border-red-700 px-2 py-1 text-xs text-red-300 hover:bg-red-950/30" type="submit">Deaktivovat</button>
                <?php else: ?>
                  <button class="rounded-md border border-emerald-700 px-2 py-1 text-xs text-emerald-300 hover:bg-emerald-950/30" type="submit">Aktivovat</button>
                <?php endif; ?>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="px-3 py-6 text-slate-400">Žádní uživatelé.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($p->pages > 1): ?>
    <div class="flex flex-wrap items-center gap-1 mt-4">
      <?php
        // první/předchozí/další/poslední + okno stránek
        $urlFirst = Url::build($routeForPager, ['page'=>$p->firstPage()]);
        $urlPrev  = Url::build($routeForPager, ['page'=>$p->prevPage()]);
        $urlNext  = Url::build($routeForPager, ['page'=>$p->nextPage()]);
        $urlLast  = Url::build($routeForPager, ['page'=>$p->lastPage()]);
      ?>
      <a href="<?= $urlFirst ?>"
         class="px-3 py-1 rounded border border-slate-700 <?= $p->hasPrev() ? 'hover:bg-slate-800' : 'opacity-50 pointer-events-none' ?>">« První</a>
      <a href="<?= $urlPrev  ?>"
         class="px-3 py-1 rounded border border-slate-700 <?= $p->hasPrev() ? 'hover:bg-slate-800' : 'opacity-50 pointer-events-none' ?>">‹ Předchozí</a>

      <?php foreach ($p->window(2) as $pg): ?>
        <?php if ($pg === null): ?>
          <span class="px-2 text-slate-400">…</span>
        <?php else:
          $active = ($pg === $p->page);
          $url    = Url::build($routeForPager, ['page'=>$pg]);
        ?>
          <a href="<?= $url ?>"
             class="px-3 py-1 rounded <?= $active ? 'bg-sky-500 text-slate-950' : 'bg-slate-800 text-slate-200 hover:bg-slate-700' ?>">
            <?= $pg ?>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>

      <a href="<?= $urlNext ?>"
         class="px-3 py-1 rounded border border-slate-700 <?= $p->hasNext() ? 'hover:bg-slate-800' : 'opacity-50 pointer-events-none' ?>">Další ›</a>
      <a href="<?= $urlLast ?>"
         class="px-3 py-1 rounded border border-slate-700 <?= $p->hasNext() ? 'hover:bg-slate-800' : 'opacity-50 pointer-events-none' ?>">Poslední »</a>
    </div>
  <?php endif; ?>
</section>
