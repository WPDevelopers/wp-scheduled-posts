import React from 'react'

export default function Facebook({ page, group }) {
    return (
        <React.Fragment>
            <div className='wpsp-modal-social-platform'>
                <div className='entry-head facebook'>
                    <img
                        src='https://itushar.me/dev/wp-content/plugins/wp-scheduled-posts/admin/assets/images/icon-facebook-small-white.png'
                        alt='logo'
                    />
                    <h2 className='entry-head-title'>Facebook</h2>
                </div>
                <ul>
                    <li>Pages: </li>
                    {page.map((item, index) => (
                        <li id={'facebook_page_' + index} key={index}>
                            <div className='item-content'>
                                <div className='entry-thumbnail'>
                                    <img
                                        src='https://scontent-lax3-1.xx.fbcdn.net/v/t1.0-1/cp0/p50x50/104447021_103269271446191_8892114688067945178_o.png?_nc_cat=104&amp;_nc_sid=dbb9e7&amp;_nc_ohc=X_6m8nD-nooAX8Duvu3&amp;_nc_ht=scontent-lax3-1.xx&amp;oh=61b337157a9eca69e54506b10d5d42ac&amp;oe=5FAB5877'
                                        alt='logo'
                                    />
                                </div>
                                <h4 className='entry-title'>{item.name}</h4>
                                <div className='control'>
                                    <input
                                        type='checkbox'
                                        name='pagekey'
                                        value='0'
                                    />
                                    <div></div>
                                </div>
                            </div>
                        </li>
                    ))}

                    <li>Groups: </li>
                    {group.map((item, index) => (
                        <li id={'facebook_group_' + index} key={index}>
                            <div className='item-content'>
                                <div className='entry-thumbnail'>
                                    <img
                                        src='https://scontent-lax3-1.xx.fbcdn.net/v/t1.0-0/cp0/c4.0.50.50a/p50x50/92595996_125168682430858_158405003831148544_o.jpg?_nc_cat=110&amp;_nc_sid=ca434c&amp;_nc_ohc=PTOt5c-m8f0AX_1QHr9&amp;_nc_ht=scontent-lax3-1.xx&amp;_nc_tp=27&amp;oh=0c4fb3be6bedbb2941357af39e6d6f66&amp;oe=5FAB1EF7'
                                        alt='logo'
                                    />
                                </div>
                                <h4 className='entry-title'>{item.name}</h4>
                                <div className='control'>
                                    <input
                                        type='checkbox'
                                        name='groupkey'
                                        value='0'
                                    />
                                    <div></div>
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            </div>
        </React.Fragment>
    )
}
