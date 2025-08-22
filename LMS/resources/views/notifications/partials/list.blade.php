@if($notifications->count() > 0)
    @foreach($notifications as $notification)
        <div class="notification-item p-3 border-bottom {{ !$notification->is_read ? 'bg-light' : '' }}" data-id="{{ $notification->id }}">
            <div class="d-flex align-items-start">
                <div class="me-2">
                    @if($notification->type === 'new_letter')
                        <i class="fas fa-envelope text-primary"></i>
                    @elseif($notification->type === 'letter_access_granted')
                        <i class="fas fa-key text-success"></i>
                    @else
                        <i class="fas fa-bell text-info"></i>
                    @endif
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <h6 class="mb-0 {{ !$notification->is_read ? 'fw-bold' : '' }}" style="font-size: 0.85rem;">{{ $notification->title }}</h6>
                        <div class="d-flex align-items-center gap-1">
                            @if(!$notification->is_read)
                                <button class="btn btn-sm btn-outline-success" onclick="markAsRead({{ $notification->id }})" title="Tandai Dibaca" style="padding: 0.1rem 0.3rem; font-size: 0.7rem;">
                                    <i class="fas fa-check"></i>
                                </button>
                            @endif
                            <span class="badge bg-primary" style="font-size: 0.6rem;">
                                @if($notification->created_at->isToday())
                                    {{ $notification->created_at->format('H:i') }}
                                @elseif($notification->created_at->isYesterday())
                                    Kemarin
                                @else
                                    {{ $notification->created_at->format('d/m') }}
                                @endif
                            </span>
                        </div>
                    </div>
                    <p class="mb-1 text-muted" style="font-size: 0.8rem; line-height: 1.3;">{{ $notification->message }}</p>
                    
                    @if($notification->data)
                        <div class="mb-2">
                            @if(isset($notification->data['division_name']))
                                <span class="badge bg-secondary me-1" style="font-size: 0.6rem;">{{ $notification->data['division_name'] }}</span>
                            @endif
                            @if(isset($notification->data['jenis_surat_name']))
                                <span class="badge bg-info me-1" style="font-size: 0.6rem;">{{ $notification->data['jenis_surat_name'] }}</span>
                            @endif
                            @if(isset($notification->data['letter_number']))
                                <span class="badge bg-dark" style="font-size: 0.6rem;">{{ $notification->data['letter_number'] }}</span>
                            @endif
                        </div>
                    @endif
                    
                    @if($notification->surat)
                        <div class="mt-2">
                            <a href="{{ route('notifications.view', $notification->id) }}" class="btn btn-sm btn-primary" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;">
                                <i class="fas fa-eye me-1"></i> Lihat Surat
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
@else
    <div class="text-center p-4">
        <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
        <p class="text-muted mb-0" style="font-size: 0.85rem;">Tidak ada notifikasi</p>
    </div>
@endif
