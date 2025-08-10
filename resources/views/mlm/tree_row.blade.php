@php
    $left = $user->leftChild;
    $right = $user->rightChild;
@endphp

@if($left || $right)
<tr id="children-{{ $user->id }}">
    <td class="pl-{{ ($level + 1) * 4 }}">
        <table class="w-full">
            <tbody>
                @if($left)
                    @include('mlm.tree_row', ['user' => $left, 'level' => $level + 1, 'prefix' => 'L → '])
                @endif
                @if($right)
                    @include('mlm.tree_row', ['user' => $right, 'level' => $level + 1, 'prefix' => 'R → '])
                @endif
            </tbody>
        </table>
    </td>
</tr>
@endif
<span onclick="loadUserDetail('{{ $user->id }}')" class="cursor-pointer hover:underline">
    └─ {{ $prefix }}{{ $user->username }} ({{ $user->full_name }})
</span>