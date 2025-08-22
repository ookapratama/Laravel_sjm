@extends('layouts.app')
@section('content')
<div class="page-inner">
    <h4 class="page-title">Statistik Keuangan</h4>
    <div class="row">
        <!-- Bar Harian -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Pemasukan & Pengeluaran Harian</div>
                <div class="card-body">
                    <div id="lineChart" style="height:300px;"></div>
                </div>
            </div>
        </div>

        <!-- Pie Pemasukan -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Pemasukan Bulan Ini</div>
                <div class="card-body">
                    <div id="incomePieChart" style="height:320px;"></div>
                </div>
            </div>
        </div>

        <!-- Pie Pengeluaran -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Pengeluaran Bulan Ini</div>
                <div class="card-body">
                    <div id="expensePieChart" style="height:320px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
/* ===== Data dari server ===== */
const daily      = @json($daily);
const incomePie  = @json($incomePie);
const expensePie = @json($expensePie);

/* ===== Util ===== */
const palette = ['#4CAF50','#2196F3','#FFC107','#F44336','#795548','#9C27B0'];
const safeNum = (v) => Number.isFinite(+v) ? +v : 0;
const rupiah  = (v) => new Intl.NumberFormat('id-ID', {
  style:'currency', currency:'IDR', minimumFractionDigits:0, maximumFractionDigits:0
}).format(safeNum(v));

document.addEventListener('DOMContentLoaded', () => {
  if (!window.echarts) { console.error('ECharts tidak ter-load'); return; }

  // Containers
  const areaEl    = document.getElementById('lineChart');
  const incomeEl  = document.getElementById('incomePieChart');
  const expenseEl = document.getElementById('expensePieChart');

  // Init charts
  const areaChart    = echarts.init(areaEl, null, { renderer: 'canvas' });
  const incomeChart  = echarts.init(incomeEl, null, { renderer: 'canvas' });
  const expenseChart = echarts.init(expenseEl, null, { renderer: 'canvas' });

  /* ===== Stacked Area (harian) ===== */
  const dates = daily.map(d => d.date);
  const sPin  = daily.map(d => safeNum(d.penjualan_pin));
  const sProd = daily.map(d => safeNum(d.produk));
  const sMng  = daily.map(d => safeNum(d.manajemen));
  const sPair = daily.map(d => safeNum(d.pairing_bonus));
  const sWd   = daily.map(d => safeNum(d.withdraw));

  const dataZoom = dates.length > 14 ? [
    { type: 'inside' },
    { type: 'slider', height: 18, bottom: 0 }
  ] : [];

  areaChart.setOption({
    color: palette,
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'cross' },
      formatter: (params) => {
        const date = params?.[0]?.axisValueLabel || '';
        const rows = params
          .filter(p => p.seriesType === 'line')
          .map(p => `${p.marker} ${p.seriesName}: ${rupiah(p.data || 0)}`);
        return `${date}<br/>${rows.join('<br/>')}`;
      }
    },
    legend: { top: 0 },
    grid: { left: 10, right: 10, bottom: dataZoom.length ? 30 : 10, top: 40, containLabel: true },
    xAxis: { type: 'category', boundaryGap: false, data: dates },
    yAxis: { type: 'value', axisLabel: { formatter: (v) => rupiah(v) }, splitLine: { show: true } },
    dataZoom,
    series: [
      { name: 'Penjualan Pin', type:'line', stack:'total', smooth:true, showSymbol:true, symbolSize:6, areaStyle:{ opacity:.35 }, data: sPin },
      { name: 'Produk',        type:'line', stack:'total', smooth:true, showSymbol:true, symbolSize:6, areaStyle:{ opacity:.35 }, data: sProd },
      { name: 'Manajemen',     type:'line', stack:'total', smooth:true, showSymbol:true, symbolSize:6, areaStyle:{ opacity:.35 }, data: sMng },
      { name: 'Pairing Bonus', type:'line', stack:'total', smooth:true, showSymbol:true, symbolSize:6, areaStyle:{ opacity:.35 }, data: sPair },
      { name: 'Withdraw',      type:'line', stack:'total', smooth:true, showSymbol:true, symbolSize:6, areaStyle:{ opacity:.35 }, data: sWd,
        label: { show:true, position:'top', formatter: p => rupiah(p.value) } },
      // OPSIONAL: total di puncak
      { name:'Total', type:'line', data: dates.map((_,i)=>sPin[i]+sProd[i]+sMng[i]+sPair[i]+sWd[i]),
        z:5, symbol:'none', label:{show:true, position:'top', formatter:p=>rupiah(p.value)},
        lineStyle:{width:0}, areaStyle:{opacity:0} }
    ],
    emphasis: { focus: 'series' },
    animationDuration: 600,
    animationEasing: 'quadraticOut'
  });

  /* ===== Pie: Pemasukan ===== */
  const incomeSeries = [
    { name: 'Penjualan Pin', value: safeNum(incomePie.penjualan_pin) },
    { name: 'Produk',        value: safeNum(incomePie.produk) },
    { name: 'Manajemen',     value: safeNum(incomePie.manajemen) },
  ];
  incomeChart.setOption({
    color: [palette[0], palette[1], palette[2]],
    title: { text: 'Pemasukan Bulan Ini', left: 'center' },
    legend: { bottom: 0, data: incomeSeries.map(d => d.name) },
    tooltip: { trigger: 'item', formatter: (p) => `${p.marker} ${p.name}: ${rupiah(p.value)} (${p.percent}%)` },
    series: [{
      name: 'Pemasukan', type: 'pie', radius: ['45%','70%'], center: ['50%','45%'],
      label: { show: true, formatter: '{b}\n{d}%' },
      labelLine: { length: 12, length2: 8 },
      emphasis: { itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0,0,0,.35)' } },
      data: incomeSeries
    }]
  });

  /* ===== Pie: Pengeluaran ===== */
  const expenseSeries = [
    { name: 'Pairing Bonus', value: safeNum(expensePie.pairing_bonus) },
    { name: 'Withdraw',      value: safeNum(expensePie.withdraw) },
  ];
  expenseChart.setOption({
    color: [palette[3], palette[5]],
    title: { text: 'Pengeluaran Bulan Ini', left: 'center' },
    legend: { bottom: 0, data: expenseSeries.map(d => d.name) },
    tooltip: { trigger: 'item', formatter: (p) => `${p.marker} ${p.name}: ${rupiah(p.value)} (${p.percent}%)` },
    series: [{
      name: 'Pengeluaran', type: 'pie', radius: ['45%','70%'], center: ['50%','45%'],
      label: { show: true, formatter: '{b}\n{d}%' },
      labelLine: { length: 12, length2: 8 },
      emphasis: { itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0,0,0,.35)' } },
      data: expenseSeries
    }]
  });

  /* ===== Responsif (debounced + ResizeObserver) ===== */
  const resizeAll = () => { areaChart.resize(); incomeChart.resize(); expenseChart.resize(); };
  let rAF = null;
  const debounced = () => { if (rAF) cancelAnimationFrame(rAF); rAF = requestAnimationFrame(resizeAll); };

  window.addEventListener('resize', debounced);
  if ('ResizeObserver' in window) {
    const ro = new ResizeObserver(debounced);
    ro.observe(areaEl); ro.observe(incomeEl); ro.observe(expenseEl);
  }

  // Cleanup
  window.addEventListener('beforeunload', () => {
    areaChart.dispose(); incomeChart.dispose(); expenseChart.dispose();
  });
});
</script>
@endpush
