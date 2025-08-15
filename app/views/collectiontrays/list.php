<?php
// v1.0
use App\Helpers\Url;
use App\Helpers\Sort;
use App\Helpers\View;

function sortLink($label,$col,$curS,$curD){
  $next = Sort::nextDir($col,$curS,$curD);
  $url  = Url::build('collectiontrays/list',['sort'=>$col,'dir'=>$next,'page'=>1]);
  $c = ($curS===$col)?($curD==='ASC'?' ▲':' ▼'):'';
  return '<a href="'.$url.'" class="hover:underline">'.$label.$c.'</a>';
}
$routeForPager = $_GET['route'] ?? 'collectiontrays/list';
?>
<section class="space-y-4">
  <div class="flex items-center justify-between">
    <h1 class="text-lg font-semibold">Moje plata</h1>
    <a href="<?= Url::build('collectiontrays/create') ?>" class="rounded-md bg-sky-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400">+ Nové plato</a>
  </div>

  <form method="get" class="flex gap-2">
    <input type="hidden" name="route" value="collectiontrays/list">
    <input class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
           type="text" name="q" placeholder="Hledat v názvu nebo popisu…"
           value="<?= htmlspecialchars($q ?? '') ?>">
    <button class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">Hledat</button>
  </form>

  <?= View::flashHtml() ?>

  <div class="overflow-x-auto rounded-xl border border-slate-800">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-900 text-slate-400">
        <tr>
          <th class="px-3 py-2 text-left"><?= sortLink('Pořadí','position',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Název','name',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left hidden md:table-cell"><?= sortLink('Popis','description',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-right">Akce</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-800">
        <?php foreach ($rows as $r): ?>
          <tr class="hover:bg-slate-800/40">
            <td class="px-3 py-2">
              <div class="inline-flex gap-2">
                <form method="post" action="<?= Url::build('collectiontrays/moveUp') ?>">
                  <?= View::csrfField() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="rounded-md border border-slate-700 px-2 py-1 text-xs hover:bg-slate-800" title="Nahoru">↑</button>
                </form>
                <form method="post" action="<?= Url::build('collectiontrays/moveDown') ?>">
                  <?= View::csrfField() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="rounded-md border border-slate-700 px-2 py-1 text-xs hover:bg-slate-800" title="Dolů">↓</button>
                </form>
              </div>
            </td>
            <td class="px-3 py-2 font-medium"><?= htmlspecialchars($r['name']) ?></td>
            <td class="px-3 py-2 hidden md:table-cell"><?= htmlspecialchars($r['description'] ?? '') ?></td>
            <td class="px-3 py-2 text-right">
              <a class="rounded-md border border-slate-700 px-2 py-1 text-xs hover:bg-slate-800"
                 href="<?= Url::build('collectiontrays/edit',['id'=>$r['id']]) ?>">Upravit</a>
              <form class="inline-block" method="post" action="<?= Url::build('collectiontrays/delete') ?>"
                    onsubmit="return confirm('Opravdu smazat toto plato? Mince v tomto platu zůstanou, jen bez přiřazení.');">
                <?= View::csrfField() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="rounded-md border border-red-700 px-2 py-1 text-xs text-red-300 hover:bg-red-950/30" type="submit">Smazat</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
          <tr><td colspan="4" class="px-3 py-6 text-slate-400">Zatím žádná plata.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($p->pages > 1): ?>
    <div class="flex flex-wrap items-center gap-1 mt-4">
      <?php
        $urlFirst = Url::build($routeForPager, ['page'=>$p->firstPage()]);
        $urlPrev  = Url::build($routeForPager, ['page'=>$p->prevPage()]);
        $urlNext  = Url::build($routeForPager, ['page'=>$p->nextPage()]);
        $urlLast  = Url::build($routeForPager, ['page'=>$p->lastPage()]);
      ?>
      <a href="<?= $urlFirst ?>" class="px-3 py-1 rounded border border-slate-700 <?= $p->hasPrev()?'hover:bg-slate-800':'opacity-50 pointer-events-none' ?>">« První</a>
      <a href="<?= $urlPrev  ?>" class="px-3 py-1 rounded border border-slate-700 <?= $p->hasPrev()?'hover:bg-slate-800':'opacity-50 pointer-events-none' ?>">‹ Předchozí</a>
      <?php foreach ($p->window(2) as $pg): ?>
        <?php if ($pg===null): ?><span class="px-2 text-slate-400">…</span><?php else:
          $active = ($pg===$p->page); $url=Url::build($routeForPager,['page'=>$pg]); ?>
          <a href="<?= $url ?>" class="px-3 py-1 rounded <?= $active?'bg-sky-500 text-slate-950':'bg-slate-800 text-slate-200 hover:bg-slate-700' ?>"><?= $pg ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
      <a href="<?= $urlNext ?>" class="px-3 py-1 rounded border border-slate-700 <?= $p->hasNext()?'hover:bg-slate-800':'opacity-50 pointer-events-none' ?>">Další ›</a>
      <a href="<?= $urlLast ?>" class="px-3 py-1 rounded border border-slate-700 <?= $p->hasNext()?'hover:bg-slate-800':'opacity-50 pointer-events-none' ?>">Poslední »</a>
    </div>
  <?php endif; ?>
</section>
